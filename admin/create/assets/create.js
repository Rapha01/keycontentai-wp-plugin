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
    var isRunning = false;
    
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
        return new Promise((resolve, reject) => {
            $.ajax({
                url: keycontentaiCreate.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'keycontentai_generate_content',
                    keyword: keyword,
                    nonce: keycontentaiCreate.nonce
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
