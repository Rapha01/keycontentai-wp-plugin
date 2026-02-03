/**
 * SparkWP Load Keywords Page JavaScript
 */

jQuery(document).ready(function($) {
    var $keywordsInput = $('#sparkwp-keywords');
    var $additionalContextInput = $('#sparkwp-additional-context');
    var $keywordCount = $('#sparkwp-keyword-count');
    var $contextCount = $('#sparkwp-context-count');
    var $contextCountWrapper = $('#sparkwp-context-count-wrapper');
    var $contextWrapper = $('#sparkwp-context-wrapper');
    var $enableContext = $('#sparkwp-enable-context');
    var $startBtn = $('#sparkwp-start-btn');
    var $stopBtn = $('#sparkwp-stop-btn');
    var $clearBtn = $('#sparkwp-clear-btn');
    var $form = $('#sparkwp-load-keywords-form');
    var $toggleDebugBtn = $('#sparkwp-toggle-debug-btn');
    var $debugContainer = $('#sparkwp-debug-container');
    var $debugOutput = $('#sparkwp-debug-output');
    var $clearDebugBtn = $('#sparkwp-clear-debug-btn');
    var $autoPublish = $('#sparkwp-auto-publish');
    var isRunning = false;
    var debugVisible = false;
    
    // Toggle additional context textarea
    $enableContext.on('change', function() {
        if ($(this).is(':checked')) {
            $contextWrapper.slideDown(300);
            $contextCountWrapper.show();
            updateContextCount();
        } else {
            $contextWrapper.slideUp(300);
            $contextCountWrapper.hide();
        }
    });
    
    // Toggle debug container
    $toggleDebugBtn.on('click', function() {
        if (debugVisible) {
            // Hide debug
            $debugContainer.slideUp(300, function() {
                // Clear debug output when hiding
                $debugOutput.html('<div class="sparkwp-loadkeywords-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
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
        $debugOutput.html('<div class="sparkwp-loadkeywords-debug-empty"><span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span><p>Debug information will appear here when generation starts.</p></div>');
    });
    
    // Update keyword count
    function updateKeywordCount() {
        var keywords = $keywordsInput.val().trim().split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        $keywordCount.text(keywords.length);
    }
    
    // Update context count
    function updateContextCount() {
        var contexts = $additionalContextInput.val().trim().split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        $contextCount.text(contexts.length);
    }
    
    $keywordsInput.on('input', updateKeywordCount);
    $additionalContextInput.on('input', updateContextCount);
    
    // Clear keywords
    $clearBtn.on('click', function() {
        if (confirm(sparkwpLoadKeywords.confirmClear)) {
            $keywordsInput.val('');
            $additionalContextInput.val('');
            updateKeywordCount();
            updateContextCount();
        }
    });
    
    // Add debug entry
    function addDebugEntry(title, content) {
        var timestamp = new Date().toLocaleTimeString();
        
        // Remove empty state if present
        $debugOutput.find('.sparkwp-loadkeywords-debug-empty').remove();
        
        var entry = $('<div class="sparkwp-loadkeywords-debug-entry"></div>');
        entry.append('<div class="sparkwp-loadkeywords-debug-entry-header">' + title + '<span class="sparkwp-loadkeywords-debug-timestamp">' + timestamp + '</span></div>');
        entry.append('<div class="sparkwp-loadkeywords-debug-entry-content">' + (typeof content === 'object' ? JSON.stringify(content, null, 2) : content) + '</div>');
        
        $debugOutput.append(entry);
        $debugOutput.scrollTop($debugOutput[0].scrollHeight);
    }
    
    // Get first keyword from textarea
    function getFirstKeyword() {
        var text = $keywordsInput.val();
        var lines = text.split('\n');
        
        for (var i = 0; i < lines.length; i++) {
            var keyword = lines[i].trim();
            if (keyword !== '') {
                return keyword;
            }
        }
        
        return null;
    }
    
    // Get first context from textarea
    function getFirstContext() {
        if (!$enableContext.is(':checked')) {
            return null;
        }
        
        var text = $additionalContextInput.val();
        var lines = text.split('\n');
        
        // Always get the FIRST line, even if empty, to maintain line correspondence
        if (lines.length > 0) {
            var context = lines[0].trim();
            // Return null if empty, otherwise return the context
            return context !== '' ? context : null;
        }
        
        return null;
    }
    
    // Remove first keyword from textarea
    function removeFirstKeyword() {
        var text = $keywordsInput.val();
        var lines = text.split('\n');
        var foundFirst = false;
        
        // Find and remove the first non-empty line
        var newLines = [];
        for (var i = 0; i < lines.length; i++) {
            if (!foundFirst && lines[i].trim() !== '') {
                foundFirst = true;
                continue; // Skip this line (remove it)
            }
            newLines.push(lines[i]);
        }
        
        $keywordsInput.val(newLines.join('\n'));
        updateKeywordCount();
    }
    
    // Remove first context from textarea
    function removeFirstContext() {
        if (!$enableContext.is(':checked')) {
            return;
        }
        
        var text = $additionalContextInput.val();
        var lines = text.split('\n');
        
        // Always remove the FIRST line, even if empty, to maintain line correspondence
        if (lines.length > 0) {
            lines.shift(); // Remove first line
            $additionalContextInput.val(lines.join('\n'));
            updateContextCount();
        }
    }
    
    // Form submission
    $form.on('submit', async function(e) {
        e.preventDefault();
        
        var firstKeyword = getFirstKeyword();
        if (!firstKeyword) {
            alert(sparkwpLoadKeywords.noKeywords);
            return;
        }
        
        // Toggle buttons
        $startBtn.prop('disabled', true).hide();
        $stopBtn.show();
        $keywordsInput.prop('readonly', true);
        isRunning = true;
        
        // Process keywords sequentially
        await processKeywords();
    });
    
    // Process keywords one by one with async/await
    async function processKeywords() {
        var createdCount = 0;
        var existingCount = 0;
        var autoPublish = $autoPublish.is(':checked');
        var useContext = $enableContext.is(':checked');
        
        while (isRunning) {
            // Get the first keyword from textarea
            var keyword = getFirstKeyword();
            
            // If no more keywords, stop
            if (!keyword) {
                break;
            }
            
            // Get the first context if enabled
            var additionalContext = useContext ? getFirstContext() : null;
            
            try {
                // Call the AJAX endpoint
                var result = await processKeyword(keyword, autoPublish, additionalContext);
                
                // Track counts
                if (result.exists) {
                    existingCount++;
                } else {
                    createdCount++;
                }
                
                // Display debug info if available
                if (result.debug && result.debug.length > 0) {
                    addDebugEntry('Keyword: ' + keyword, result.debug);
                }
                
                // Remove the processed keyword from textarea
                removeFirstKeyword();
                
                // Remove the processed context from textarea (if enabled)
                if (useContext) {
                    removeFirstContext();
                }
                
            } catch (error) {
                // On error, still remove the keyword to prevent infinite loop
                removeFirstKeyword();
                if (useContext) {
                    removeFirstContext();
                }
            }
            
            // Small delay between keywords
            await sleep(500);
        }
        
        resetForm();
    }
    
    // Process a single keyword
    async function processKeyword(keyword, autoPublish, additionalContext) {
        // Check if debug container is visible
        var debugMode = $debugContainer.is(':visible');
        
        var ajaxData = {
            action: 'sparkwp_load_keyword',
            keyword: keyword,
            nonce: sparkwpLoadKeywords.nonce,
            debug: debugMode ? '1' : '0',
            auto_publish: autoPublish ? '1' : '0'
        };
        
        // Add additional context if provided
        if (additionalContext !== null && additionalContext !== undefined) {
            ajaxData.additional_context = additionalContext;
        }
        
        return new Promise((resolve, reject) => {
            $.ajax({
                url: sparkwpLoadKeywords.ajaxUrl,
                type: 'POST',
                data: ajaxData,
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
        if (confirm(sparkwpLoadKeywords.confirmStop)) {
            isRunning = false;
        }
    });
});

