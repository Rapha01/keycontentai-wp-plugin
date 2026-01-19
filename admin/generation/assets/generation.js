/**
 * KeyContentAI Generation Page JavaScript
 */

(function($) {
    'use strict';
    
    // Generation state
    let isGenerating = false;
    let stopRequested = false;
    
    $(document).ready(function() {
        console.log('KeyContentAI Generation Page loaded');
        
        // Initialize event listeners
        initEventListeners();
        updateQueueCount();
    });
    
    /**
     * Initialize all event listeners
     */
    function initEventListeners() {
        // Individual queue toggle buttons
        $(document).on('click', '.keycontentai-toggle-queue', function(e) {
            e.preventDefault();
            const $row = $(this).closest('.keycontentai-post-row');
            const status = $row.attr('data-status');
            
            // Don't allow toggling while processing
            if (status === 'processing') {
                return;
            }
            
            // Toggle between unqueued and queued
            if (status === 'unqueued') {
                queuePost($row);
            } else if (status === 'queued') {
                unqueuePost($row);
            } else if (status === 'finished') {
                // Allow re-queueing finished posts
                queuePost($row);
            }
        });
        
        // Queue All button
        $('#keycontentai-queue-all').on('click', function(e) {
            e.preventDefault();
            $('.keycontentai-post-row').each(function() {
                const status = $(this).attr('data-status');
                if (status === 'unqueued' || status === 'finished') {
                    queuePost($(this));
                }
            });
        });
        
        // Unqueue All button
        $('#keycontentai-unqueue-all').on('click', function(e) {
            e.preventDefault();
            $('.keycontentai-post-row').each(function() {
                const status = $(this).attr('data-status');
                if (status === 'queued') {
                    unqueuePost($(this));
                }
            });
        });
        
        // Start Generation button
        $('#keycontentai-start-generation').on('click', function(e) {
            e.preventDefault();
            
            if (isGenerating) {
                // Stop generation
                stopGeneration();
            } else {
                // Start generation
                startGeneration();
            }
        });
    }
    
    /**
     * Add debug entry (wrapper for global debug function)
     */
    function addDebugEntry(step, data, isError) {
        if (window.KeyContentAIDebug && typeof window.KeyContentAIDebug.addEntry === 'function') {
            window.KeyContentAIDebug.addEntry(step, data, isError);
        }
    }
    
    /**
     * Queue a post for generation
     */
    function queuePost($row) {
        // Update UI
        updatePostStatus($row, 'queued');
        updateQueueCount();
    }
    
    /**
     * Remove a post from queue
     */
    function unqueuePost($row) {
        // Update UI
        updatePostStatus($row, 'unqueued');
        updateQueueCount();
    }
    
    /**
     * Get count of queued posts
     */
    function getQueuedCount() {
        return $('.keycontentai-post-row[data-status="queued"]').length;
    }
    
    /**
     * Get next queued post
     */
    function getNextQueuedPost() {
        return $('.keycontentai-post-row[data-status="queued"]').first();
    }
    
    /**
     * Update post row status
     */
    function updatePostStatus($row, newStatus) {
        $row.attr('data-status', newStatus);
        
        const $indicator = $row.find('.keycontentai-status-indicator');
        const $button = $row.find('.keycontentai-toggle-queue');
        
        // Remove all status classes
        $indicator.removeClass('keycontentai-status-unqueued keycontentai-status-queued keycontentai-status-processing keycontentai-status-finished keycontentai-status-error');
        
        // Add new status class
        $indicator.addClass('keycontentai-status-' + newStatus);
        
        // Update button text
        switch(newStatus) {
            case 'unqueued':
                $button.text('Queue').prop('disabled', false);
                break;
            case 'queued':
                $button.text('Unqueue').prop('disabled', false);
                break;
            case 'processing':
                $button.text('Processing...').prop('disabled', true);
                break;
            case 'finished':
                $button.text('Re-queue').prop('disabled', false);
                break;
            case 'error':
                $button.text('Re-queue').prop('disabled', false);
                break;
        }
    }
    
    /**
     * Update queue count display
     */
    function updateQueueCount() {
        const count = getQueuedCount();
        const $button = $('#keycontentai-start-generation');
        
        if (isGenerating) {
            // Show as Stop button during generation
            $button
                .prop('disabled', false)
                .removeClass('button-primary')
                .addClass('button-secondary')
                .text('Stop Generation');
        } else {
            // Show as Start button when idle
            $button
                .prop('disabled', count === 0)
                .removeClass('button-secondary')
                .addClass('button-primary')
                .text(count > 0 ? `Start Generation (${count})` : 'Start Generation');
        }
    }
    
    /**
     * Start the generation process
     */
    function startGeneration() {
        const queuedCount = getQueuedCount();
        
        if (queuedCount === 0 || isGenerating) {
            return;
        }
        
        isGenerating = true;
        stopRequested = false;
        console.log('Starting generation for', queuedCount, 'posts');
        
        // Disable queue management buttons
        $('#keycontentai-queue-all, #keycontentai-unqueue-all')
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
        $('#keycontentai-start-generation')
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
        
        const postId = $row.attr('data-post-id');
        
        // Update status to processing
        updatePostStatus($row, 'processing');
        
        console.log('Processing post ID:', postId);
        
        // AJAX call to generate content
        $.ajax({
            url: keycontentaiGeneration.ajaxUrl,
            type: 'POST',
            data: {
                action: 'keycontentai_generate_content',
                post_id: postId,
                nonce: keycontentaiGeneration.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Add debug data from response
                    if (response.data && response.data.debug_log) {
                        response.data.debug_log.forEach(function(debugEntry) {
                            addDebugEntry(debugEntry.step, debugEntry.data, false);
                        });
                    }
                    onPostSuccess($row);
                } else {
                    addDebugEntry('Generation failed', response.data, true);
                    onPostError($row, response.data);
                }
            },
            error: function(xhr, status, error) {
                addDebugEntry('Network error', {
                    status: status,
                    error: error
                }, true);
                onPostError($row, 'Network error');
            }
        });
    }
    
    /**
     * Handle successful generation
     */
    function onPostSuccess($row) {
        // Update UI
        updatePostStatus($row, 'finished');
        
        // Update last generated timestamp
        const now = new Date();
        const timestamp = now.toLocaleString();
        $row.find('.last-generated-cell').text(timestamp);
        
        // Process next post
        processNextPost();
    }
    
    /**
     * Handle generation error
     */
    function onPostError($row, errorMessage) {
        const postId = $row.attr('data-post-id');
        
        // Update UI
        updatePostStatus($row, 'error');
        
        console.error('Generation failed for post', postId, ':', errorMessage);
        
        // Process next post
        processNextPost();
    }
    
    /**
     * Finish generation process
     */
    function finishGeneration(wasStopped) {
        isGenerating = false;
        stopRequested = false;
        
        if (wasStopped) {
            console.log('Generation stopped');
        } else {
            console.log('Generation complete!');
        }
        
        // Re-enable action buttons
        $('#keycontentai-queue-all, #keycontentai-unqueue-all').prop('disabled', false);
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
    function showNotice(message, type) {
        type = type || 'info'; // info, success, warning, error
        
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Insert after page title
        $('.wrap > h1').after($notice);
        
        // Add dismiss button functionality
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 50
        }, 300);
    }
    
})(jQuery);
