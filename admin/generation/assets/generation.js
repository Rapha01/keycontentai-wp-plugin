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
     * If the server returns { status: 'processing', job_id: '...' }, automatically
     * polls check_job_status until the job completes, then resolves with the result.
     */
    function ajaxPromise(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: sparkplusGeneration.ajaxUrl,
                type: 'POST',
                timeout: 30000, // Short — server responds immediately now
                data: { ...data, nonce: sparkplusGeneration.nonce },
                success: function(response) {
                    if (response.success && response.data?.status === 'processing' && response.data?.job_id) {
                        // Server is processing async — poll for completion
                        pollJobStatus(response.data.job_id, resolve, reject);
                    } else {
                        resolve(response);
                    }
                },
                error: (xhr, status, error) => reject({ xhr, status, error }),
            });
        });
    }

    /**
     * Poll check_job_status every 2s until the job is complete or errors.
     * Resolves with a response shaped like a normal wp_send_json_success/error response.
     *
     * @param {string}   jobId    Transient key returned by the server
     * @param {Function} resolve  Promise resolve
     * @param {Function} reject   Promise reject
     * @param {number}   attempts Number of polls already made
     */
    function pollJobStatus(jobId, resolve, reject, attempts = 0, debugOffset = 0) {
        const maxAttempts = 180; // 6 minutes at 2s intervals

        if (attempts >= maxAttempts) {
            reject({ error: 'Job polling timed out after 6 minutes' });
            return;
        }

        setTimeout(function() {
            $.ajax({
                url: sparkplusGeneration.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: {
                    action: 'sparkplus_check_job_status',
                    job_id: jobId,
                    nonce: sparkplusGeneration.nonce,
                },
                success: function(response) {
                    if (!response.success) {
                        // Job expired or lookup error
                        reject({ error: response.data?.message || 'Job status check failed' });
                        return;
                    }

                    const job = response.data;
                    const allEntries = job.debug_log || [];

                    // Stream any new debug entries to the panel immediately
                    const newEntries = allEntries.slice(debugOffset);
                    newEntries.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));
                    const nextOffset = debugOffset + newEntries.length;

                    if (job.status === 'complete') {
                        // Resolve with empty debug_log — entries already streamed above
                        resolve({ success: true, data: { debug_log: [] } });
                    } else if (job.status === 'error') {
                        resolve({ success: false, data: { message: job.message, debug_log: [] } });
                    } else {
                        // Still pending — keep polling
                        pollJobStatus(jobId, resolve, reject, attempts + 1, nextOffset);
                    }
                },
                error: function() {
                    // Transient network hiccup — retry rather than fail
                    pollJobStatus(jobId, resolve, reject, attempts + 1, debugOffset);
                },
            });
        }, 2000);
    }

    /**
     * Orchestrate the full generation for a single post:
     *  1. Fetch metadata (lightweight — returns field lists only)
     *  2. Generate all text fields in one call (if any)
     *  3. Generate each image field one-by-one
     *  4. Stamp generation timestamp
     */
    async function generatePost(postId, $row) {
        try {
            // 1. Fetch field metadata
            const metaResp = await ajaxPromise({ action: 'sparkplus_get_generation_meta', post_id: postId });
            metaResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

            if (!metaResp.success) {
                handleError($row, 'get_generation_meta', metaResp.data?.message || 'Failed to fetch generation metadata', 'server');
                return;
            }

            const { text_fields = [], image_fields = [], has_clear_fields = false } = metaResp.data;

            // Nothing to do — no generate fields and no clear fields
            if (text_fields.length === 0 && image_fields.length === 0 && !has_clear_fields) {
                handleError($row, 'validation', 'No fields selected for generation or clearing', 'client');
                return;
            }

            // 2. Generate text fields
            if (text_fields.length > 0) {
                const textResp = await ajaxPromise({ action: 'sparkplus_generate_text', post_id: postId });
                textResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

                if (!textResp.success) {
                    handleError($row, 'generate_text', textResp.data?.message || 'Text generation failed', 'server');
                    return;
                }
            }

            // 3. Generate image fields one at a time
            for (const img of image_fields) {
                const imgResp = await ajaxPromise({
                    action:      'sparkplus_generate_image',
                    post_id:     postId,
                    field_index: img.index,
                });
                imgResp.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false, 'server'));

                if (!imgResp.success) {
                    handleError($row, 'generate_image', imgResp.data?.message || 'Image generation failed (' + img.label + ')', 'server');
                    return;
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
