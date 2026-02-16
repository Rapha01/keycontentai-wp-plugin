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
                        
                        // Update keyword display in main row
                        const $row = $detailsRow.prev('.sparkplus-post-row');
                        $row.find('td[data-label="Keyword"] strong').text(keyword || '(no keyword)');
                        
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
    function addDebugEntry(step, data, isError) {
        window.SparkPlusDebug?.addEntry(step, data, isError);
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
        
        // AJAX call to generate content
        $.ajax({
            url: sparkplusGeneration.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sparkplus_generate_content',
                post_id: postId,
                nonce: sparkplusGeneration.nonce
            },
            success: function(response) {
                // Add debug data from response
                response.data?.debug_log?.forEach(entry => addDebugEntry(entry.step, entry.data, false));
                onPostComplete($row, response.success, response.data);
            },
            error: function(xhr, status, error) {
                addDebugEntry('Network error', { status, error }, true);
                onPostComplete($row, false, 'Network error');
            }
        });
    }
    
    /**
     * Handle post generation result
     */
    function onPostComplete($row, success, errorMessage) {
        updatePostStatus($row, success ? 'finished' : 'error');
        
        if (success) {
            $row.find('.last-generated-cell').text(new Date().toLocaleString());
        } else {
            console.error('Generation failed for post', $row.data('post-id'), ':', errorMessage);
        }
        
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
