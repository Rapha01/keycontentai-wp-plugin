/**
 * KeyContentAI Generation Debug JavaScript
 */

(function($) {
    'use strict';
    
    // Debug state
    let debugVisible = false;
    let debugData = {
        all: [],
        lastTextPrompt: null,
        lastImagePrompt: null,
        lastTextResponse: null,
        lastImageResponse: null
    };
    
    // Export debug functions to global scope for use by generation.js
    window.KeyContentAIDebug = {
        init: initDebugListeners,
        addEntry: addDebugEntry,
        clear: clearDebug,
        isVisible: function() { return debugVisible; }
    };
    
    $(document).ready(function() {
        initDebugListeners();
    });
    
    /**
     * Initialize debug event listeners
     */
    function initDebugListeners() {
        // Toggle debug container
        $('#keycontentai-toggle-debug-btn').on('click', function() {
            if (debugVisible) {
                // Hide debug
                $('#keycontentai-debug-container').slideUp(300);
                $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-admin-tools');
                $(this).find('.button-text').text('Show Debug Mode');
                debugVisible = false;
            } else {
                // Show debug
                $('#keycontentai-debug-container').slideDown(300);
                $(this).find('.dashicons').removeClass('dashicons-admin-tools').addClass('dashicons-hidden');
                $(this).find('.button-text').text('Hide Debug Mode');
                debugVisible = true;
            }
        });
        
        // Clear debug output
        $('#keycontentai-clear-debug-btn').on('click', function() {
            clearDebug();
        });
        
        // Tab switching
        $('.keycontentai-debug-tab').on('click', function() {
            const tabName = $(this).data('tab');
            switchDebugTab(tabName);
        });
    }
    
    /**
     * Switch debug tab
     */
    function switchDebugTab(tabName) {
        // Update tab buttons
        $('.keycontentai-debug-tab').removeClass('active');
        $(`.keycontentai-debug-tab[data-tab="${tabName}"]`).addClass('active');
        
        // Update tab content
        $('.keycontentai-debug-tab-content').removeClass('active');
        $(`#keycontentai-debug-tab-${tabName}`).addClass('active');
    }
    
    /**
     * Clear all debug data
     */
    function clearDebug() {
        debugData = {
            all: [],
            lastTextPrompt: null,
            lastImagePrompt: null,
            lastTextResponse: null,
            lastImageResponse: null
        };
        
        const emptyState = (message) => 
            '<div class="keycontentai-generation-debug-empty">' +
            '<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>' +
            '<p>' + message + '</p>' +
            '</div>';
        
        $('#keycontentai-debug-tab-all').html(emptyState('Debug information will appear here when generation starts.'));
        $('#keycontentai-debug-tab-text-prompt').html(emptyState('Last text prompt will appear here after generation.'));
        $('#keycontentai-debug-tab-image-prompt').html(emptyState('Last image prompt will appear here after generation.'));
        $('#keycontentai-debug-tab-text-response').html(emptyState('Last text API response will appear here after generation.'));
        $('#keycontentai-debug-tab-image-response').html(emptyState('Last image API response will appear here after generation.'));
    }
    
    /**
     * Add debug entry
     */
    function addDebugEntry(step, data, isError) {
        const timestamp = new Date().toLocaleTimeString();
        
        // Add to debug data
        debugData.all.push({
            step: step,
            data: data,
            timestamp: timestamp,
            isError: isError || false
        });
        
        // Extract prompt and response if available
        if (typeof data === 'object' && data !== null) {
            // Check for text prompt (from build_text_prompt)
            if (step === 'build_text_prompt' && data.prompt) {
                debugData.lastTextPrompt = data.prompt;
            }
            
            // Check for image prompt (from build_image_prompt - singular, one at a time)
            if (step === 'build_image_prompt' && data.prompt) {
                debugData.lastImagePrompt = data.prompt;
            }
            
            // Check for API request (alternative source for prompts)
            if (data.request_body && data.request_body.messages) {
                const messages = data.request_body.messages;
                if (step === 'generate_text') {
                    debugData.lastTextPrompt = JSON.stringify(messages, null, 2);
                }
            }
            
            // Check for API response (standardized as full_api_response)
            if (data.full_api_response) {
                if (step === 'generate_image') {
                    debugData.lastImageResponse = data.full_api_response;
                } else if (step === 'generate_text') {
                    debugData.lastTextResponse = data.full_api_response;
                }
            }
        }
        
        // Update all debug tab
        updateAllDebugTab();
        
        // Update text prompt tab if we have one
        if (debugData.lastTextPrompt) {
            updateTextPromptTab();
        }
        
        // Update image prompt tab if we have one
        if (debugData.lastImagePrompt) {
            updateImagePromptTab();
        }
        
        // Update text response tab if we have one
        if (debugData.lastTextResponse) {
            updateTextResponseTab();
        }
        
        // Update image response tab if we have one
        if (debugData.lastImageResponse) {
            updateImageResponseTab();
        }
    }
    
    /**
     * Update "All Debug Data" tab
     */
    function updateAllDebugTab() {
        const $container = $('#keycontentai-debug-tab-all');
        $container.empty();
        
        debugData.all.forEach(function(entry) {
            const $entry = $('<div class="keycontentai-debug-entry"></div>');
            if (entry.isError) {
                $entry.addClass('error');
            }
            
            const $header = $('<div class="keycontentai-debug-entry-header"></div>')
                .text(entry.step)
                .append('<span class="keycontentai-debug-timestamp">' + entry.timestamp + '</span>');
            
            const content = typeof entry.data === 'object' 
                ? JSON.stringify(entry.data, null, 2) 
                : entry.data;
            
            const $content = $('<div class="keycontentai-debug-entry-content"></div>').text(content);
            
            $entry.append($header).append($content);
            $container.append($entry);
        });
        
        // Scroll to bottom
        $container.scrollTop($container[0].scrollHeight);
    }
    
    /**
     * Update specific tab with content
     */
    function updateTab(tabId, content, className) {
        const $container = $(`#keycontentai-debug-tab-${tabId}`);
        $container.html(`<div class="${className}">${content}</div>`);
    }
    
    /**
     * Update "Last Text Prompt" tab
     */
    function updateTextPromptTab() {
        updateTab('text-prompt', debugData.lastTextPrompt, 'keycontentai-debug-prompt');
    }
    
    /**
     * Update "Last Image Prompt" tab
     */
    function updateImagePromptTab() {
        updateTab('image-prompt', debugData.lastImagePrompt, 'keycontentai-debug-prompt');
    }
    
    /**
     * Update "Last Text API Response" tab
     */
    function updateTextResponseTab() {
        updateTab('text-response', debugData.lastTextResponse, 'keycontentai-debug-response');
    }
    
    /**
     * Update "Last Image API Response" tab
     */
    function updateImageResponseTab() {
        updateTab('image-response', debugData.lastImageResponse, 'keycontentai-debug-response');
    }
    
})(jQuery);
