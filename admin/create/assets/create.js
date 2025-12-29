/**
 * KeyContentAI Create Page JavaScript
 */

jQuery(document).ready(function($) {
    var $keywordsInput = $('#keycontentai-keywords');
    var $keywordCount = $('#keycontentai-keyword-count');
    var $startBtn = $('#keycontentai-start-btn');
    var $stopBtn = $('#keycontentai-stop-btn');
    var $clearBtn = $('#keycontentai-clear-btn');
    var $clearLogBtn = $('#keycontentai-clear-log-btn');
    var $log = $('#keycontentai-log');
    var $form = $('#keycontentai-create-form');
    var $toggleDebugBtn = $('#keycontentai-toggle-debug-btn');
    var $debugContainer = $('#keycontentai-debug-container');
    var $debugOutput = $('#keycontentai-debug-output');
    var $promptOutput = $('#keycontentai-prompt-output');
    var $clearDebugBtn = $('#keycontentai-clear-debug-btn');
    var $debugTabs = $('.keycontentai-debug-tab');
    var isRunning = false;
    var debugVisible = false;
    
    // Debug tab switching
    $debugTabs.on('click', function() {
        var tab = $(this).data('tab');
        
        // Update tab buttons
        $debugTabs.removeClass('keycontentai-debug-tab-active');
        $(this).addClass('keycontentai-debug-tab-active');
        
        // Update tab panes
        $('.keycontentai-debug-pane').removeClass('keycontentai-debug-pane-active');
        $('#keycontentai-debug-tab-' + tab).addClass('keycontentai-debug-pane-active');
    });
    
    // Toggle debug container
    $toggleDebugBtn.on('click', function() {
        if (debugVisible) {
            // Hide debug
            $debugContainer.slideUp(300, function() {
                // Clear debug output when hiding
                $debugOutput.html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
            });
            $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-admin-tools');
            $(this).find('.button-text').text('Show Debug Mode');
            debugVisible = false;
        } else {
            // Show debug
            $debugContainer.slideDown(300);
            $(this).find('.dashicons').removeClass('dashicons-admin-tools').addClass('dashicons-hidden');
            $(this).find('.button-text').text('Hide Debug Mode');
            debugVisible = true;
        }
    });
    
    // Clear debug output
    $clearDebugBtn.on('click', function() {
        $debugOutput.html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
        $promptOutput.html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-edit-large" style="font-size: 48px; opacity: 0.3;"></span><p>The latest generated prompt will appear here.</p></div>');
        $('#keycontentai-api-response-output').html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-cloud" style="font-size: 48px; opacity: 0.3;"></span><p>The latest text API response will appear here.</p></div>');
    });
    
    // Update keyword count
    function updateKeywordCount() {
        var keywords = $keywordsInput.val().trim().split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        $keywordCount.text(keywords.length);
    }
    
    $keywordsInput.on('input', updateKeywordCount);
    
    // Clear keywords
    $clearBtn.on('click', function() {
        if (confirm(keycontentaiCreate.confirmClear)) {
            $keywordsInput.val('');
            updateKeywordCount();
        }
    });
    
    // Clear log
    $clearLogBtn.on('click', function() {
        $log.html('<div class="keycontentai-log-empty"><span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span><p>' + keycontentaiCreate.logEmpty + '</p></div>');
    });
    
    // Add log entry
    function addLogEntry(message, type) {
        type = type || 'info';
        var timestamp = new Date().toLocaleTimeString();
        
        // Remove empty state if present
        $log.find('.keycontentai-log-empty').remove();
        
        var entry = $('<div class="keycontentai-log-entry log-' + type + '">' +
            '<span class="keycontentai-log-timestamp">[' + timestamp + ']</span>' +
            '<span class="keycontentai-log-message">' + message + '</span>' +
            '</div>');
        
        $log.append(entry);
        $log.scrollTop($log[0].scrollHeight);
    }
    
    // Add debug entry
    function addDebugEntry(title, content) {
        var timestamp = new Date().toLocaleTimeString();
        
        // Remove empty state if present
        $debugOutput.find('.keycontentai-debug-empty').remove();
        
        var entry = $('<div class="keycontentai-debug-entry"></div>');
        entry.append('<div class="keycontentai-debug-entry-header">' + title + '<span class="keycontentai-debug-timestamp">' + timestamp + '</span></div>');
        entry.append('<div class="keycontentai-debug-entry-content">' + (typeof content === 'object' ? JSON.stringify(content, null, 2) : content) + '</div>');
        
        $debugOutput.append(entry);
        $debugOutput.scrollTop($debugOutput[0].scrollHeight);
        
        // Extract and display prompt if available
        if (typeof content === 'object' && Array.isArray(content)) {
            // Find build_prompt step in debug array
            for (var i = 0; i < content.length; i++) {
                if (content[i].step === 'build_prompt' && content[i].data && content[i].data.prompt_length) {
                    // Look for the next entry which might contain the full prompt
                    // Or check if there's a prompt preview we can use
                    continue;
                }
                
                // Check if this step contains actual prompt data
                if (content[i].step === 'build_prompt' && content[i].data) {
                    var promptData = content[i].data;
                    
                    // Try to find the prompt in various possible locations
                    var prompt = null;
                    
                    // Check if prompt_preview exists
                    if (promptData.prompt_preview) {
                        // This is just a preview, but we'll use it for now
                        prompt = promptData.prompt_preview;
                    }
                }
            }
            
            // Actually, let's look for the full prompt in the entire debug array
            // The prompt should be in the build_prompt step
            extractAndDisplayPrompt(content);
            
            // Also extract and display the API response
            extractAndDisplayApiResponse(content);
        }
    }
    
    // Extract and display the prompt from debug data
    function extractAndDisplayPrompt(debugArray) {
        var textPrompt = null;
        var imagePrompts = null;
        var combinedPrompt = '';
        
        // Loop through debug entries to find the prompts
        for (var i = 0; i < debugArray.length; i++) {
            var entry = debugArray[i];
            
            // Look for text prompt (build_text_prompt step)
            if (entry.step === 'build_text_prompt' && entry.data && entry.data.full_prompt) {
                textPrompt = entry.data.full_prompt;
            }
            
            // Look for image prompts (build_image_prompts step)
            if (entry.step === 'build_image_prompts' && entry.data && entry.data.full_prompt) {
                imagePrompts = entry.data.full_prompt;
            }
        }
        
        // Build combined prompt display
        if (textPrompt) {
            combinedPrompt += '=== TEXT GENERATION PROMPT (GPT) ===\n\n' + textPrompt;
        }
        
        if (imagePrompts) {
            if (combinedPrompt) {
                combinedPrompt += '\n\n\n';
            }
            combinedPrompt += '=== IMAGE GENERATION PROMPTS (DALL-E) ===\n\n' + imagePrompts;
        }
        
        // Display the combined prompts
        if (combinedPrompt) {
            // Remove empty state if present
            $promptOutput.find('.keycontentai-debug-empty').remove();
            
            // Display the full prompt
            $promptOutput.html('<pre style="margin: 0; white-space: pre-wrap; word-break: break-word; font-family: \'Courier New\', monospace; font-size: 12px; line-height: 1.6;">' + escapeHtml(combinedPrompt) + '</pre>');
        } else {
            // If no prompts found, show empty state
            $promptOutput.html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-edit-large" style="font-size: 48px; opacity: 0.3;"></span><p>No prompt data found in this debug session.</p></div>');
        }
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Function to extract and display the latest API response from debug data
    function extractAndDisplayApiResponse(debugArray) {
        var apiResponse = null;
        var $apiResponseOutput = $('#keycontentai-api-response-output');
        
        // Loop through debug entries to find the API response
        for (var i = 0; i < debugArray.length; i++) {
            var entry = debugArray[i];
            
            // Look for generate_text step with full_api_response
            if (entry.step === 'generate_text' && entry.data && entry.data.full_api_response) {
                apiResponse = entry.data.full_api_response;
                
                // Remove empty state if present
                $apiResponseOutput.find('.keycontentai-debug-empty').remove();
                
                // Try to format as JSON if possible
                try {
                    var parsed = JSON.parse(apiResponse);
                    apiResponse = JSON.stringify(parsed, null, 2);
                } catch (e) {
                    // Not JSON, display as-is
                }
                
                // Display the API response
                $apiResponseOutput.html('<pre style="margin: 0; white-space: pre-wrap; word-break: break-word; font-family: \'Courier New\', monospace; font-size: 12px; line-height: 1.6;">' + escapeHtml(apiResponse) + '</pre>');
                return;
            }
        }
        
        // If no API response found, show empty state
        if (!apiResponse) {
            $apiResponseOutput.html('<div class="keycontentai-debug-empty"><span class="dashicons dashicons-cloud" style="font-size: 48px; opacity: 0.3;"></span><p>No text API response data found in this debug session.</p></div>');
        }
    }
    
    // Form submission
    $form.on('submit', async function(e) {
        e.preventDefault();
        
        var keywords = $keywordsInput.val().trim().split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        
        if (keywords.length === 0) {
            alert(keycontentaiCreate.noKeywords);
            return;
        }
        
        // Toggle buttons
        $startBtn.prop('disabled', true).hide();
        $stopBtn.show();
        $keywordsInput.prop('readonly', true);
        isRunning = true;
        
        // Initial log entries
        addLogEntry(keycontentaiCreate.starting, 'info');
        addLogEntry(keycontentaiCreate.found + ' ' + keywords.length + ' ' + keycontentaiCreate.keywordsToProcess, 'info');
        addLogEntry('---', 'info');
        
        // Process keywords sequentially
        await processKeywords(keywords);
    });
    
    // Process keywords one by one with async/await
    async function processKeywords(keywords) {
        var index = 0;
        
        while (index < keywords.length && isRunning) {
            var keyword = keywords[index].trim();
            var currentNum = index + 1;
            
            // Log processing start
            addLogEntry(keycontentaiCreate.processing + ' [' + currentNum + '/' + keywords.length + ']: "' + keyword + '"', 'info');
            
            try {
                // Call the AJAX endpoint
                var result = await processKeyword(keyword);
                
                // Log success with returned data
                addLogEntry('  └─ ' + result.message, 'success');
                if (result.post_id > 0) {
                    addLogEntry('  └─ Post ID: ' + result.post_id, 'info');
                }
                
                // Display debug info if available
                if (result.debug && result.debug.length > 0) {
                    addDebugEntry('Keyword: ' + keyword, result.debug);
                }
                
            } catch (error) {
                addLogEntry('  └─ ' + keycontentaiCreate.error + ' ' + error, 'error');
            }
            
            // Small delay between keywords
            await sleep(500);
            
            index++;
        }
        
        // Check if completed or stopped
        if (isRunning) {
            addLogEntry('---', 'info');
            addLogEntry(keycontentaiCreate.allProcessed, 'success');
            addLogEntry(keycontentaiCreate.generated + ' ' + keywords.length + ' ' + keycontentaiCreate.postsSuccess, 'success');
        } else {
            addLogEntry(keycontentaiCreate.stoppedByUser, 'warning');
        }
        
        resetForm();
    }
    
    // Process a single keyword
    async function processKeyword(keyword) {
        // Check if debug container is visible
        var debugMode = $debugContainer.is(':visible');
        
        return new Promise((resolve, reject) => {
            $.ajax({
                url: keycontentaiCreate.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'keycontentai_generate_content',
                    keyword: keyword,
                    nonce: keycontentaiCreate.nonce,
                    debug: debugMode ? '1' : '0'
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data.message || 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    reject(error || 'AJAX request failed');
                }
            });
        });
    }
    
    // Helper function for delays
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Reset form after completion
    function resetForm() {
        $startBtn.prop('disabled', false).show();
        $stopBtn.hide();
        $keywordsInput.prop('readonly', false);
        isRunning = false;
    }
    
    // Stop button
    $stopBtn.on('click', function() {
        if (confirm(keycontentaiCreate.confirmStop)) {
            isRunning = false;
            addLogEntry(keycontentaiCreate.stopping, 'warning');
        }
    });
});
