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
            const status = $row.data('status');
            
            // Toggle queue state (not during processing)
            if (status !== 'processing') {
                setQueueState($row, status !== 'queued');
            }
        });
        
        // Queue All button
        $('#keycontentai-queue-all').on('click', function(e) {
            e.preventDefault();
            $('.keycontentai-post-row').filter((_, el) => ['unqueued', 'finished', 'error'].includes($(el).data('status')))
                .each((_, el) => setQueueState($(el), true));
        });
        
        // Unqueue All button
        $('#keycontentai-unqueue-all').on('click', function(e) {
            e.preventDefault();
            $('.keycontentai-post-row[data-status="queued"]').each((_, el) => setQueueState($(el), false));
        });
        
        // Start/Stop Generation button
        $('#keycontentai-start-generation').on('click', function(e) {
            e.preventDefault();
            isGenerating ? stopGeneration() : startGeneration();
        });
    }
    
    /**
     * Add debug entry
     */
    function addDebugEntry(step, data, isError) {
        window.KeyContentAIDebug?.addEntry(step, data, isError);
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
        return $('.keycontentai-post-row[data-status="queued"]').length;
    }
    
    /**
     * Get next queued post
     */
    function getNextQueuedPost() {
        return $('.keycontentai-post-row[data-status="queued"]').first();
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
        $row.find('.keycontentai-status-indicator').attr('class', 'keycontentai-status-indicator keycontentai-status-' + newStatus);
        
        const state = buttonStates[newStatus];
        if (state) {
            $row.find('.keycontentai-toggle-queue').text(state.text).prop('disabled', state.disabled);
        }
    }
    
    /**
     * Update queue count display
     */
    function updateQueueCount() {
        const count = getQueuedCount();
        const $button = $('#keycontentai-start-generation');
        
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
        
        const postId = $row.data('post-id');
        
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
    function showNotice(message, type = 'info') {
        const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.wrap > h1').after($notice);
        
        // WordPress will add dismiss functionality automatically
        // Just make it dismissible
        if (typeof wp !== 'undefined' && wp.notices) {
            wp.notices.initialize();
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => $notice.fadeOut(300, function() { $(this).remove(); }), 5000);
        
        // Scroll to notice
        $('html, body').animate({ scrollTop: $notice.offset().top - 50 }, 300);
    }
    
})(jQuery);
