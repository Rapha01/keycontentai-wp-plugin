/**
 * KeyContentAI Generation Debug JavaScript
 */

(function($) {
    'use strict';
    
    // Debug state
    let debugVisible = false;
    let debugData = {
        all: [],
        lastPrompt: null,
        lastResponse: null
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
            lastPrompt: null,
            lastResponse: null
        };
        
        // Clear all tabs
        $('#keycontentai-debug-tab-all').html(
            '<div class="keycontentai-generation-debug-empty">' +
            '<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>' +
            '<p>Debug information will appear here when generation starts.</p>' +
            '</div>'
        );
        
        $('#keycontentai-debug-tab-prompt').html(
            '<div class="keycontentai-generation-debug-empty">' +
            '<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>' +
            '<p>Last prompt will appear here after generation.</p>' +
            '</div>'
        );
        
        $('#keycontentai-debug-tab-response').html(
            '<div class="keycontentai-generation-debug-empty">' +
            '<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>' +
            '<p>Last API response will appear here after generation.</p>' +
            '</div>'
        );
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
            // Check for prompt in various formats
            if (data.prompt) {
                debugData.lastPrompt = data.prompt;
            } else if (data.request_body && data.request_body.messages) {
                // Extract from API request
                const messages = data.request_body.messages;
                debugData.lastPrompt = JSON.stringify(messages, null, 2);
            }
            
            // Check for API response
            if (data.full_api_response) {
                debugData.lastResponse = data.full_api_response;
            } else if (data.api_response) {
                debugData.lastResponse = JSON.stringify(data.api_response, null, 2);
            } else if (data.response_data) {
                debugData.lastResponse = JSON.stringify(data.response_data, null, 2);
            }
        }
        
        // Update all debug tab
        updateAllDebugTab();
        
        // Update prompt tab if we have one
        if (debugData.lastPrompt) {
            updatePromptTab();
        }
        
        // Update response tab if we have one
        if (debugData.lastResponse) {
            updateResponseTab();
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
     * Update "Last Prompt" tab
     */
    function updatePromptTab() {
        const $container = $('#keycontentai-debug-tab-prompt');
        $container.html('<div class="keycontentai-debug-prompt"></div>');
        $container.find('.keycontentai-debug-prompt').text(debugData.lastPrompt);
    }
    
    /**
     * Update "Last API Response" tab
     */
    function updateResponseTab() {
        const $container = $('#keycontentai-debug-tab-response');
        $container.html('<div class="keycontentai-debug-response"></div>');
        $container.find('.keycontentai-debug-response').text(debugData.lastResponse);
    }
    
})(jQuery);
