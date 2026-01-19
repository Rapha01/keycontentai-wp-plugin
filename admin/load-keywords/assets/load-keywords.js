/**
 * KeyContentAI Load Keywords Page JavaScript
 */

jQuery(document).ready(function($) {
    var $keywordsInput = $('#keycontentai-keywords');
    var $keywordCount = $('#keycontentai-keyword-count');
    var $startBtn = $('#keycontentai-start-btn');
    var $stopBtn = $('#keycontentai-stop-btn');
    var $clearBtn = $('#keycontentai-clear-btn');
    var $clearLogBtn = $('#keycontentai-clear-log-btn');
    var $log = $('#keycontentai-log');
    var $form = $('#keycontentai-load-keywords-form');
    var $toggleDebugBtn = $('#keycontentai-toggle-debug-btn');
    var $debugContainer = $('#keycontentai-debug-container');
    var $debugOutput = $('#keycontentai-debug-output');
    var $clearDebugBtn = $('#keycontentai-clear-debug-btn');
    var $autoPublish = $('#keycontentai-auto-publish');
    var isRunning = false;
    var debugVisible = false;
    
    // Toggle debug container
    $toggleDebugBtn.on('click', function() {
        if (debugVisible) {
            // Hide debug
            $debugContainer.slideUp(300, function() {
                // Clear debug output when hiding
                $debugOutput.html('<div class="keycontentai-loadkeywords-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
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
        $debugOutput.html('<div class="keycontentai-loadkeywords-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
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
        if (confirm(keycontentaiLoadKeywords.confirmClear)) {
            $keywordsInput.val('');
            updateKeywordCount();
        }
    });
    
    // Clear log
    $clearLogBtn.on('click', function() {
        $log.html('<div class="keycontentai-loadkeywords-log-empty"><span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span><p>' + keycontentaiLoadKeywords.logEmpty + '</p></div>');
    });
    
    // Add log entry
    function addLogEntry(message, type) {
        type = type || 'info';
        var timestamp = new Date().toLocaleTimeString();
        
        // Remove empty state if present
        $log.find('.keycontentai-loadkeywords-log-empty').remove();
        
        var entry = $('<div class="keycontentai-loadkeywords-log-entry keycontentai-log-' + type + '">' +
            '<span class="keycontentai-loadkeywords-log-timestamp">[' + timestamp + ']</span>' +
            '<span class="keycontentai-loadkeywords-log-message">' + message + '</span>' +
            '</div>');
        
        $log.append(entry);
        $log.scrollTop($log[0].scrollHeight);
    }
    
    // Add debug entry
    function addDebugEntry(title, content) {
        var timestamp = new Date().toLocaleTimeString();
        
        // Remove empty state if present
        $debugOutput.find('.keycontentai-loadkeywords-debug-empty').remove();
        
        var entry = $('<div class="keycontentai-loadkeywords-debug-entry"></div>');
        entry.append('<div class="keycontentai-loadkeywords-debug-entry-header">' + title + '<span class="keycontentai-loadkeywords-debug-timestamp">' + timestamp + '</span></div>');
        entry.append('<div class="keycontentai-loadkeywords-debug-entry-content">' + (typeof content === 'object' ? JSON.stringify(content, null, 2) : content) + '</div>');
        
        $debugOutput.append(entry);
        $debugOutput.scrollTop($debugOutput[0].scrollHeight);
    }
    
    // Form submission
    $form.on('submit', async function(e) {
        e.preventDefault();
        
        var keywords = $keywordsInput.val().trim().split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        
        if (keywords.length === 0) {
            alert(keycontentaiLoadKeywords.noKeywords);
            return;
        }
        
        // Toggle buttons
        $startBtn.prop('disabled', true).hide();
        $stopBtn.show();
        $keywordsInput.prop('readonly', true);
        isRunning = true;
        
        // Initial log entries
        addLogEntry(keycontentaiLoadKeywords.starting, 'info');
        addLogEntry(keycontentaiLoadKeywords.found + ' ' + keywords.length + ' ' + keycontentaiLoadKeywords.keywordsToProcess, 'info');
        addLogEntry('---', 'info');
        
        // Process keywords sequentially
        await processKeywords(keywords);
    });
    
    // Process keywords one by one with async/await
    async function processKeywords(keywords) {
        var index = 0;
        var createdCount = 0;
        var existingCount = 0;
        var autoPublish = $autoPublish.is(':checked');
        
        while (index < keywords.length && isRunning) {
            var keyword = keywords[index].trim();
            var currentNum = index + 1;
            
            // Log processing start
            addLogEntry(keycontentaiLoadKeywords.processing + ' [' + currentNum + '/' + keywords.length + ']: "' + keyword + '"', 'info');
            
            try {
                // Call the AJAX endpoint
                var result = await processKeyword(keyword, autoPublish);
                
                // Log success with returned data
                if (result.exists) {
                    // Post already exists
                    addLogEntry('  └─ ' + result.message, 'warning');
                    existingCount++;
                } else {
                    // New post created
                    addLogEntry('  └─ ' + result.message, 'success');
                    createdCount++;
                }
                
                if (result.post_id > 0) {
                    addLogEntry('  └─ Post ID: ' + result.post_id, 'info');
                }
                
                // Display debug info if available
                if (result.debug && result.debug.length > 0) {
                    addDebugEntry('Keyword: ' + keyword, result.debug);
                }
                
            } catch (error) {
                addLogEntry('  └─ ' + keycontentaiLoadKeywords.error + ' ' + error, 'error');
            }
            
            // Small delay between keywords
            await sleep(500);
            
            index++;
        }
        
        // Check if completed or stopped
        if (isRunning) {
            addLogEntry('---', 'info');
            addLogEntry(keycontentaiLoadKeywords.allProcessed, 'success');
            addLogEntry('Created: ' + createdCount + ' | Already existed: ' + existingCount + ' | Total: ' + keywords.length, 'info');
        } else {
            addLogEntry(keycontentaiLoadKeywords.stoppedByUser, 'warning');
        }
        
        resetForm();
    }
    
    // Process a single keyword
    async function processKeyword(keyword, autoPublish) {
        // Check if debug container is visible
        var debugMode = $debugContainer.is(':visible');
        
        return new Promise((resolve, reject) => {
            $.ajax({
                url: keycontentaiLoadKeywords.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'keycontentai_load_keyword',
                    keyword: keyword,
                    nonce: keycontentaiLoadKeywords.nonce,
                    debug: debugMode ? '1' : '0',
                    auto_publish: autoPublish ? '1' : '0'
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
        if (confirm(keycontentaiLoadKeywords.confirmStop)) {
            isRunning = false;
            addLogEntry(keycontentaiLoadKeywords.stopping, 'warning');
        }
    });
});

