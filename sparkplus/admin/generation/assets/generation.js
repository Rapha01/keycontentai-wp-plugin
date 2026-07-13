/**
 * SparkPlus Generation Page JavaScript
 */

(function($) {
    'use strict';
    
    // Generation state
    let isGenerating = false;
    let stopRequested = false;
    
    $(document).ready(function() {
        console.log('SparkPlus Generation Page loaded');
        
        // Initialize event listeners
        initEventListeners();
        updateQueueCount();
    });
    
    /**
     * Initialize all event listeners
     */
    function initEventListeners() {
        // Toggle post details
        $(document).on('click', '.sparkplus-toggle-details', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $row = $button.closest('.sparkplus-post-row');
            const $detailsRow = $row.next('.sparkplus-post-details-row');
            
            $detailsRow.slideToggle(200);
            $button.toggleClass('expanded');
        });
        
        // Delete post - show modal
        $(document).on('click', '.sparkplus-delete-post', function(e) {
            e.preventDefault();
            const $button = $(this);
            const postId = $button.data('post-id');
            const $detailsRow = $button.closest('.sparkplus-post-details-row');
            const $postRow = $detailsRow.prev('.sparkplus-post-row');
            const postTitle = $postRow.find('td a').first().text().trim() || '(no title)';

            // Populate and show the modal
            const $modal = $('#sparkplus-delete-modal');
            $modal.find('.sparkplus-delete-modal-post-title').text(postTitle);
            $modal.data('post-id', postId);
            $modal.data('post-row', $postRow);
            $modal.data('details-row', $detailsRow);
            $modal.data('trigger-button', $button);
            $modal.fadeIn(150);
        });

        // Modal cancel
        $(document).on('click', '.sparkplus-delete-modal-cancel, .sparkplus-delete-modal-overlay', function(e) {
            e.preventDefault();
            $('#sparkplus-delete-modal').fadeOut(150);
        });

        // Modal confirm delete
        $(document).on('click', '.sparkplus-delete-modal-confirm', function(e) {
            e.preventDefault();
            const $modal = $('#sparkplus-delete-modal');
            const postId = $modal.data('post-id');
            const $postRow = $modal.data('post-row');
            const $detailsRow = $modal.data('details-row');
            const $triggerButton = $modal.data('trigger-button');
            const $confirmBtn = $(this);

            $confirmBtn.prop('disabled', true).text(sparkplusGeneration.deleting || 'Deleting...');

            $.ajax({
                url: sparkplusGeneration.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sparkplus_delete_post',
                    post_id: postId,
                    nonce: sparkplusGeneration.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $modal.fadeOut(150);
                        $detailsRow.fadeOut(300, function() { $(this).remove(); });
                        $postRow.fadeOut(300, function() { $(this).remove(); updateQueueCount(); });
                    } else {
                        $modal.fadeOut(150);
                        showNotice(response.data?.message || 'Delete failed', 'error');
                    }
                },
                error: function() {
                    $modal.fadeOut(150);
                    showNotice('Network error', 'error');
                },
                complete: function() {
                    $confirmBtn.prop('disabled', false).text(sparkplusGeneration.deleteConfirm || 'Delete');
                }
            });
        });

        // Close modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#sparkplus-delete-modal').fadeOut(150);
            }
        });

        // Save post meta
        $(document).on('click', '.sparkplus-save-meta', function(e) {
            e.preventDefault();
            const $button = $(this);
            const postId = $button.data('post-id');
            const $detailsRow = $button.closest('.sparkplus-post-details-row');
            const $status = $detailsRow.find('.sparkplus-save-status');
            
            const keyword = $detailsRow.find(`#sparkplus-keyword-${postId}`).val();
            const context = $detailsRow.find(`#sparkplus-context-${postId}`).val();
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Saving...');
            $status.removeClass('success error').text('');
            
            $.ajax({
                url: sparkplusGeneration.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sparkplus_save_post_meta',
                    post_id: postId,
                    keyword: keyword,
                    additional_context: context,
                    nonce: sparkplusGeneration.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.addClass('success').text('Saved successfully!');
                        
                        // Clear status after 2 seconds
                        setTimeout(() => $status.fadeOut(300, function() { $(this).text('').show(); }), 2000);
                    } else {
                        $status.addClass('error').text(response.data || 'Save failed');
                    }
                },
                error: function() {
                    $status.addClass('error').text('Network error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save');
                }
            });
        });
        
        // Individual queue toggle buttons
        $(document).on('click', '.sparkplus-toggle-queue', function(e) {
            e.preventDefault();
            const $row = $(this).closest('.sparkplus-post-row');
            const status = $row.attr('data-status');
            
            // Toggle queue state (not during processing)
            if (status !== 'processing') {
                setQueueState($row, status !== 'queued');
            }
        });
        
        // Queue All button
        $('#sparkplus-queue-all').on('click', function(e) {
            e.preventDefault();
            $('.sparkplus-post-row').filter((_, el) => ['unqueued', 'finished', 'error'].includes($(el).data('status')))
                .each((_, el) => setQueueState($(el), true));
        });
        
        // Unqueue All button
        $('#sparkplus-unqueue-all').on('click', function(e) {
            e.preventDefault();
            $('.sparkplus-post-row[data-status="queued"]').each((_, el) => setQueueState($(el), false));
        });
        
        // Start/Stop Generation button
        $('#sparkplus-start-generation').on('click', function(e) {
            e.preventDefault();
            isGenerating ? stopGeneration() : startGeneration();
        });
    }
    
    /**
     * Add debug entry
     */
    function addDebugEntry(step, data, isError, source) {
        window.SparkPlusDebug?.addEntry(step, data, isError, source);
    }
    
    /**
     * Centralized error handler — logs to debug panel AND shows WordPress notice.
     * Every generation error should flow through here so behaviour is consistent.
     *
     * @param {jQuery}  $row    The post row element
     * @param {string}  step    Debug step label (e.g. 'generate_text', 'validation')
     * @param {string}  message Human-readable error message
     * @param {string}  source  'client' or 'server'
     */
    function handleError($row, step, message, source) {
        // 1. Log to debug panel
        addDebugEntry(step, { error: message }, true, source);
        
        // 2. Show WordPress admin notice
        const postId  = $row.data('post-id');
        const keyword = $row.find('.sparkplus-keyword-input').val() || $row.find('td:eq(2) strong').text().trim();
        const label   = keyword ? keyword + ' (ID ' + postId + ')' : 'Post ' + postId;
        const $notice = $('<div class="notice notice-error sparkplus-generation-notice"><p><strong>' + $('<span>').text(label).html() + ':</strong> ' + $('<span>').text(message).html() + '</p></div>');
        $('#sparkplus-generation-notices').append($notice);
        
        // 3. Console log
        console.error('Generation failed for post', postId, ':', message);
        
        // 4. Update row status and continue queue
        updatePostStatus($row, 'error');
        processNextPost();
    }
    
    /**
     * Set post queue state
     */
    function setQueueState($row, queued) {
        updatePostStatus($row, queued ? 'queued' : 'unqueued');
        updateQueueCount();
    }
    
    /**
     * Get count of queued posts
     */
    function getQueuedCount() {
        return $('.sparkplus-post-row[data-status="queued"]').length;
    }
    
    /**
     * Get next queued post
     */
    function getNextQueuedPost() {
        return $('.sparkplus-post-row[data-status="queued"]').first();
    }
    
    // Button state configuration
    const buttonStates = {
        unqueued:   { text: 'Queue',         disabled: false },
        queued:     { text: 'Unqueue',       disabled: false },
        processing: { text: 'Processing...', disabled: true },
        finished:   { text: 'Re-queue',      disabled: false },
        error:      { text: 'Re-queue',      disabled: false }
    };
    
    /**
     * Update post row status
     */
    function updatePostStatus($row, newStatus) {
        $row.attr('data-status', newStatus);
        $row.find('.sparkplus-status-indicator').attr('class', 'sparkplus-status-indicator sparkplus-status-' + newStatus);
        
        const state = buttonStates[newStatus];
        if (state) {
            $row.find('.sparkplus-toggle-queue').text(state.text).prop('disabled', state.disabled);
        }
    }
    
    /**
     * Update queue count display
     */
    function updateQueueCount() {
        const count = getQueuedCount();
        const $button = $('#sparkplus-start-generation');
        
        $button
            .prop('disabled', !isGenerating && count === 0)
            .toggleClass('button-primary', !isGenerating)
            .toggleClass('button-secondary', isGenerating)
            .text(isGenerating ? 'Stop Generation' : (count > 0 ? `Start Generation (${count})` : 'Start Generation'));
    }
    
    /**
     * Start the generation process
     */
    function startGeneration() {
        if (isGenerating) return;
        
        isGenerating = true;
        stopRequested = false;
        console.log('Starting generation for', getQueuedCount(), 'posts');
        
        // Clear previous error notices
        $('#sparkplus-generation-notices').empty();
        
        // Disable queue management buttons
        $('#sparkplus-queue-all, #sparkplus-unqueue-all')
            .prop('disabled', true);
        
        // Update button to show "Stop"
        updateQueueCount();
        
        // Process queue sequentially
        processNextPost();
    }
    
    /**
     * Stop the generation process
     */
    function stopGeneration() {
        stopRequested = true;
        console.log('Stop requested - will finish current post and stop');
        
        // Update button text to show stopping
        $('#sparkplus-start-generation')
            .prop('disabled', true)
            .text('Stopping...');
    }
    
    /**
     * Process the next post in queue
     */
    function processNextPost() {
        // Check if stop was requested
        if (stopRequested) {
            console.log('Generation stopped by user');
            finishGeneration(true);
            return;
        }
        
        // Get next queued post
        const $row = getNextQueuedPost();
        
        if ($row.length === 0) {
            // All done!
            finishGeneration(false);
            return;
        }
        
        const postId = $row.data('post-id');
        
        // Update status to processing
        updatePostStatus($row, 'processing');
        
        console.log('Processing post ID:', postId);

        // Top-down approach: fetch metadata, then loop through the work.
        generatePost(postId, $row);
    }

    /**
     * Wrap jQuery.ajax in a Promise for async/await usage.
     */
    function ajaxPromise(data, timeout = 30000) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: sparkplusGeneration.ajaxUrl,
                type: 'POST',
                timeout,
                data: { ...data, nonce: sparkplusGeneration.nonce },
                success: (response) => resolve(response),
                error: (xhr, status, error) => reject({ xhr, status, error }),
            });
        });
    }

    /**
     * Call a provider AI API directly from the browser using params built client-side.
     *
     * @param {Object} params  Object with api_url, headers, request_body
     * @returns {Promise<Object>} Parsed JSON response from the API
     * @throws {Error} If the request fails or the API returns an error status
     */
    async function callProviderApi(params) {
        const resp = await fetch(params.api_url, {
            method:  'POST',
            headers: params.headers,
            body:    JSON.stringify(params.request_body),
        });
        const data = await resp.json();
        if (!resp.ok) {
            throw new Error(data?.error?.message || `API error ${resp.status}`);
        }
        return data;
    }

    /**
     * Resolve the current value of a text field an image is linked to. Prefers the
     * value generated in THIS run (text is generated and saved before images);
     * otherwise falls back to the server-provided value (existing/manual content).
     *
     * @param {string} relatedKey Field key, or "group::sub" for ACF group sub-fields.
     * @param {Object} generated  Parsed JSON of the text generated this run.
     * @param {string} fallback   Server-provided value (existing/manual).
     * @returns {string}
     */
    function relatedFieldValue(relatedKey, generated, fallback) {
        if (!relatedKey) return fallback || '';
        let v;
        if (relatedKey.indexOf('::') !== -1) {
            const parts = relatedKey.split('::');
            v = (generated && generated[parts[0]]) ? generated[parts[0]][parts[1]] : undefined;
        } else {
            v = generated ? generated[relatedKey] : undefined;
        }
        if (v === undefined || v === null || v === '') return fallback || '';
        // Strip any HTML (wysiwyg fields) for clean prompt context.
        return String(v).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    }

    /**
     * Orchestrate the full generation for a single post, entirely from the browser:
     *  1. Fetch raw settings/data from server (one AJAX call)
     *  2. Build prompts client-side via SparkPlusPromptBuilder
     *  3. Call text provider API directly — no PHP timeout
     *  4. Save text to post via AJAX
     *  5. For each image field: build prompt, call image API directly, generate alt text, save
     *  6. Clear fields marked for clearing
     *  7. Stamp generation timestamp
     */
    async function generatePost(postId, $row) {
        try {
            // 1. Fetch raw generation data from server
            const metaResp = await ajaxPromise({ action: 'sparkplus_get_generation_meta', post_id: postId });
            metaResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

            if (!metaResp.success) {
                handleError($row, 'get_generation_meta', metaResp.data?.message || 'Failed to fetch generation metadata', 'server');
                return;
            }

            const {
                post_settings     = {},
                cpt_settings      = {},
                api_keys          = {},
                text_provider:    textProviderSlug  = 'openai',
                image_provider:   imageProviderSlug = 'openai',
                text_fields       = [],
                image_fields      = [],
                has_clear_fields  = false,
                existing_content  = [],
                linking           = null,
                wysiwyg_formatting = {},
            } = metaResp.data;

            // Nothing to do
            if (text_fields.length === 0 && image_fields.length === 0 && !has_clear_fields) {
                handleError($row, 'validation', 'No fields selected for generation or clearing', 'client');
                return;
            }

            const promptBuilder = new SparkPlusPromptBuilder();

            // Parsed text generated in this run; used to refresh the value of any
            // text field an image is linked to before building its image prompt.
            let generatedTextContent = {};

            // 2. Text generation — browser builds prompt and calls API directly
            if (text_fields.length > 0) {
                const textProvider = SparkPlusProviderFactory.getTextProvider(textProviderSlug, api_keys);
                const textPrompt   = promptBuilder.buildTextPrompt(
                    cpt_settings, post_settings, text_fields, existing_content, linking, wysiwyg_formatting
                );

                if (!textPrompt) {
                    handleError($row, 'build_text_prompt', 'Failed to build text prompt', 'client');
                    return;
                }

                addDebugEntry('build_text_prompt', { prompt: textPrompt }, false, 'client');

                addDebugEntry('generate_text', {
                    message:  'Calling ' + textProviderSlug + ' text API from browser...',
                    model:    cpt_settings.text_model,
                    provider: textProviderSlug,
                }, false, 'client');

                let textData;
                try {
                    textData = await callProviderApi(textProvider.buildTextRequest(cpt_settings.text_model, textPrompt));
                } catch (apiErr) {
                    handleError($row, 'generate_text', 'Text API error: ' + apiErr.message, 'client');
                    return;
                }

                const content = textProvider.parseTextResponse(textData);
                addDebugEntry('generate_text', { full_api_response: JSON.stringify(textData, null, 2) }, false, 'client');
                if (!content) {
                    handleError($row, 'generate_text', 'No text content in API response', 'client');
                    return;
                }

                addDebugEntry('generate_text', { message: 'Text received. Saving to post...' }, false, 'client');

                const saveTextResp = await ajaxPromise({
                    action:       'sparkplus_save_text',
                    post_id:      postId,
                    content_json: content,
                });
                saveTextResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

                if (!saveTextResp.success) {
                    handleError($row, 'save_text', saveTextResp.data?.message || 'Failed to save text', 'server');
                    return;
                }

                // Keep the freshly-generated text so an image linked to a text field
                // uses its NEW value (it was just saved above, before any image runs).
                try { generatedTextContent = JSON.parse(content) || {}; } catch (_) { generatedTextContent = {}; }
            }

            // 3. Image generation — browser builds prompts and calls API directly for each image
            if (image_fields.length > 0) {
                const imageProvider = SparkPlusProviderFactory.getImageProvider(imageProviderSlug, api_keys);
                // Text provider is used for alt text generation.
                const textProvider  = SparkPlusProviderFactory.getTextProvider(textProviderSlug, api_keys);

                for (const img of image_fields) {
                    // Refresh the linked field's value with this run's generated text
                    // (falls back to the server-provided existing/manual value).
                    if (img.related_field) {
                        img.related_value = relatedFieldValue(img.related_field, generatedTextContent, img.related_value);
                    }

                    const imagePrompt = promptBuilder.buildImagePrompt(
                        cpt_settings, post_settings, img, existing_content
                    );

                    addDebugEntry('generate_image', {
                        message:     'Calling ' + imageProviderSlug + ' image API from browser...',
                        model:       cpt_settings.image_model,
                        field_index: img.index,
                    }, false, 'client');

                    addDebugEntry('build_image_prompt', { prompt: imagePrompt }, false, 'client');

                    let imgData;
                    try {
                        imgData = await callProviderApi(imageProvider.buildImageRequest(cpt_settings.image_model, imagePrompt, img));
                    } catch (apiErr) {
                        handleError($row, 'generate_image', 'Image API error: ' + apiErr.message, 'client');
                        return;
                    }

                    const b64_json = imageProvider.parseImageResponse(imgData);
                    addDebugEntry('generate_image', { full_api_response: JSON.stringify(imgData, null, 2) }, false, 'client');
                    if (!b64_json) {
                        handleError($row, 'generate_image', 'No image data in API response', 'client');
                        return;
                    }

                    addDebugEntry('generate_image', { message: 'Image received. Generating alt text...' }, false, 'client');

                    // Generate alt text client-side using the text model (non-fatal).
                    let alt_text = null;
                    try {
                        const altPrompt = promptBuilder.buildAltTextPrompt(imagePrompt);
                        const altData   = await callProviderApi(textProvider.buildTextRequest(cpt_settings.text_model, altPrompt));
                        const rawAlt    = textProvider.parseTextResponse(altData);
                        if (rawAlt) {
                            try {
                                const parsed = JSON.parse(rawAlt);
                                alt_text = parsed?.alt_text || rawAlt;
                            } catch (_) {
                                alt_text = rawAlt;
                            }
                            alt_text = String(alt_text).trim().replace(/^["']|["']$/g, '');
                            if (alt_text.length > 125) alt_text = alt_text.slice(0, 122) + '...';
                            addDebugEntry('generate_alt_text', { message: 'Alt text: ' + alt_text }, false, 'client');
                        }
                    } catch (altErr) {
                        // Non-fatal — log and continue without alt text.
                        addDebugEntry('generate_alt_text', { message: 'Alt text generation failed: ' + altErr.message }, true, 'client');
                    }

                    addDebugEntry('generate_image', { message: 'Saving image to media library...' }, false, 'client');

                    // 120s timeout — large base64 payload.
                    const saveImgResp = await ajaxPromise({
                        action:      'sparkplus_save_generated_image',
                        post_id:     postId,
                        field_index: img.index,
                        b64_json:    b64_json,
                        alt_text:    alt_text,
                    }, 120000);
                    saveImgResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

                    if (saveImgResp.data?.attachment_url) {
                        addDebugEntry('generate_image', { attachment_url: saveImgResp.data.attachment_url }, false, 'client');
                    }

                    if (!saveImgResp.success) {
                        handleError($row, 'generate_image', saveImgResp.data?.message || 'Failed to save image', 'server');
                        return;
                    }
                }
            }

            // 4. Clear fields marked for clearing
            if (has_clear_fields) {
                const clearResp = await ajaxPromise({ action: 'sparkplus_clear_fields', post_id: postId });
                clearResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

                if (!clearResp.success) {
                    handleError($row, 'clear_fields', clearResp.data?.message || 'Field clearing failed', 'server');
                    return;
                }
            }

            // 5. Stamp generation timestamp
            await ajaxPromise({ action: 'sparkplus_stamp_generation', post_id: postId });

            onPostComplete($row);

        } catch (err) {
            const detail = err.error || err.message || 'Unknown network error';
            handleError($row, 'network', 'Network error: ' + detail, 'client');
        }
    }

    
    /**
     * Handle successful post generation
     */
    function onPostComplete($row) {
        updatePostStatus($row, 'finished');
        processNextPost();
    }
    
    /**
     * Finish generation process
     */
    function finishGeneration(wasStopped) {
        isGenerating = false;
        stopRequested = false;
        console.log(wasStopped ? 'Generation stopped' : 'Generation complete!');
        
        // Re-enable action buttons
        $('#sparkplus-queue-all, #sparkplus-unqueue-all').prop('disabled', false);
        updateQueueCount();
        
        // Show completion message as WordPress notice
        showNotice(
            wasStopped 
                ? 'Generation stopped. Remaining posts are still queued.' 
                : 'Content generation complete!',
            wasStopped ? 'warning' : 'success'
        );
    }
    
    /**
     * Show WordPress-style admin notice
     */
    function showNotice(message, type = 'info') {
        const $notice = $('<div>').addClass('notice notice-' + type + ' is-dismissible')
            .append($('<p>').text(message));
        $('.wrap > h1').after($notice);
        
        // Add dismiss button manually
        const $dismissButton = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        $notice.append($dismissButton);
        
        // Handle dismiss click
        $dismissButton.on('click', function() {
            $notice.fadeOut(300, function() { $(this).remove(); });
        });
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => $notice.fadeOut(300, function() { $(this).remove(); }), 5000);
        
        // Scroll to notice
        $('html, body').animate({ scrollTop: $notice.offset().top - 50 }, 300);
    }
    
})(jQuery);
