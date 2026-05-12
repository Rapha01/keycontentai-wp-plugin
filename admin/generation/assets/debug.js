/**
 * SparkPlus Generation Debug JavaScript
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
    window.SparkPlusDebug = {
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
        $('#sparkplus-toggle-debug-btn').on('click', function() {
            if (debugVisible) {
                // Hide debug
                $('#sparkplus-debug-container').slideUp(300);
                $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-admin-tools');
                $(this).find('.button-text').text('Show Debug Mode');
                debugVisible = false;
            } else {
                // Show debug
                $('#sparkplus-debug-container').slideDown(300);
                $(this).find('.dashicons').removeClass('dashicons-admin-tools').addClass('dashicons-hidden');
                $(this).find('.button-text').text('Hide Debug Mode');
                debugVisible = true;
            }
        });
        
        // Clear debug output
        $('#sparkplus-clear-debug-btn').on('click', function() {
            clearDebug();
        });
        
        // Tab switching
        $('.sparkplus-debug-tab').on('click', function() {
            const tabName = $(this).data('tab');
            switchDebugTab(tabName);
        });
    }
    
    /**
     * Switch debug tab
     */
    function switchDebugTab(tabName) {
        // Update tab buttons
        $('.sparkplus-debug-tab').removeClass('active');
        $(`.sparkplus-debug-tab[data-tab="${tabName}"]`).addClass('active');
        
        // Update tab content
        $('.sparkplus-debug-tab-content').removeClass('active');
        $(`#sparkplus-debug-tab-${tabName}`).addClass('active');
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
            lastImageResponse: null,
            lastImageUrl: null,
        };
        
        const emptyState = (message) => 
            '<div class="sparkplus-generation-debug-empty">' +
            '<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>' +
            '<p>' + message + '</p>' +
            '</div>';
        
        $('#sparkplus-debug-tab-all').html(emptyState('Debug information will appear here when generation starts.'));
        $('#sparkplus-debug-tab-text-prompt').html(emptyState('Last text prompt will appear here after generation.'));
        $('#sparkplus-debug-tab-image-prompt').html(emptyState('Last image prompt will appear here after generation.'));
        $('#sparkplus-debug-tab-text-response').html(emptyState('Last text API response will appear here after generation.'));
        $('#sparkplus-debug-tab-image-response').html(emptyState('Last image API response will appear here after generation.'));
    }
    
    /**
     * Add debug entry
     *
     * @param {string}  step     Label for the debug step
     * @param {*}       data     Arbitrary payload (object or string)
     * @param {boolean} isError  Whether this entry represents an error
     * @param {string}  source   Origin: 'client' or 'server'
     */
    function addDebugEntry(step, data, isError, source) {
        const timestamp = new Date().toLocaleTimeString();
        
        // Add to debug data
        debugData.all.push({
            step: step,
            data: data,
            timestamp: timestamp,
            isError: isError || false,
            source: source || null
        });
        
        // Extract prompt and response if available
        if (typeof data === 'object' && data !== null) {
            // Text prompt: only from build_text_prompt (alt text generation never fires this)
            if (step === 'build_text_prompt' && data.prompt) {
                debugData.lastTextPrompt = data.prompt;
            }
            
            // Image prompt: from build_image_prompt
            if (step === 'build_image_prompt' && data.prompt) {
                debugData.lastImagePrompt = data.prompt;
            }
            
            // Raw API responses
            if (data.full_api_response) {
                if (step === 'generate_image') {
                    debugData.lastImageResponse = data.full_api_response;
                } else if (step === 'generate_text') {
                    debugData.lastTextResponse = data.full_api_response;
                }
            }

            // Saved image URL (replaces raw response display)
            if (step === 'generate_image' && data.attachment_url) {
                debugData.lastImageUrl = data.attachment_url;
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
        
        // Update image response tab if we have a URL or raw response
        if (debugData.lastImageUrl || debugData.lastImageResponse) {
            updateImageResponseTab();
        }
    }
    
    /**
     * Update "All Debug Data" tab
     */
    function updateAllDebugTab() {
        const $container = $('#sparkplus-debug-tab-all');
        $container.empty();
        
        debugData.all.forEach(function(entry) {
            const $entry = $('<div class="sparkplus-debug-entry"></div>');
            if (entry.isError) {
                $entry.addClass('error');
            }
            
            const $header = $('<div class="sparkplus-debug-entry-header"></div>');
            
            // Source badge (Client / Server)
            if (entry.source) {
                const badgeClass = 'sparkplus-debug-source sparkplus-debug-source-' + entry.source;
                $header.append('<span class="' + badgeClass + '">' + entry.source.charAt(0).toUpperCase() + entry.source.slice(1) + '</span> ');
            }
            
            $header.append(document.createTextNode(entry.step));
            $header.append('<span class="sparkplus-debug-timestamp">' + entry.timestamp + '</span>');
            
            const content = typeof entry.data === 'object' 
                ? JSON.stringify(entry.data, null, 2) 
                : entry.data;
            
            const $content = $('<div class="sparkplus-debug-entry-content"></div>').text(content);
            
            $entry.append($header).append($content);
            $container.append($entry);
        });
        
        // Scroll to bottom
        $container.scrollTop($container[0].scrollHeight);
    }
    
    /**
     * Update specific tab with plain text content
     */
    function updateTab(tabId, content, className) {
        const $container = $(`#sparkplus-debug-tab-${tabId}`);
        $container.empty().append($('<div>').addClass(className).text(content));
    }

    /**
     * Update specific tab with Markdown-rendered content
     */
    function updateMarkdownTab(tabId, content, className) {
        const $container = $(`#sparkplus-debug-tab-${tabId}`);
        // Escape HTML entities first so literal tags like <p> render as text, not elements
        const escaped = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const html = (typeof marked !== 'undefined') ? marked.parse(escaped) : content.replace(/\n/g, '<br>');
        $container.empty().append($('<div>').addClass(className).html(html));
    }

    /**
     * Update "Last Text Prompt" tab
     */
    function updateTextPromptTab() {
        updateMarkdownTab('text-prompt', debugData.lastTextPrompt, 'sparkplus-debug-prompt');
    }
    
    /**
     * Update "Last Image Prompt" tab
     */
    function updateImagePromptTab() {
        updateMarkdownTab('image-prompt', debugData.lastImagePrompt, 'sparkplus-debug-prompt');
    }
    
    /**
     * Update "Last Text API Response" tab
     */
    function updateTextResponseTab() {
        updateTab('text-response', debugData.lastTextResponse, 'sparkplus-debug-response');
    }
    
    /**
     * Update "Last Image API Response" tab
     * Shows the saved image by its WordPress URL, plus the raw JSON below.
     */
    function updateImageResponseTab() {
        const $container = $('#sparkplus-debug-tab-image-response');
        $container.empty();

        if (debugData.lastImageUrl) {
            $container.append(
                $('<div class="sparkplus-debug-image-preview">').append(
                    $('<img>').attr('src', debugData.lastImageUrl)
                              .css({ maxWidth: '100%', display: 'block', marginBottom: '12px', borderRadius: '4px' })
                )
            );
        }

        if (debugData.lastImageResponse) {
            $container.append($('<div>').addClass('sparkplus-debug-response').text(debugData.lastImageResponse));
        }
    }
    
})(jQuery);
