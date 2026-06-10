/**
 * SparkPlus Internal Linking Tab JavaScript
 */

jQuery(document).ready(function($) {
    
    // In-memory data structure for linking pool
    var linkingPool = {
        post_types: [], // Array of post type slugs that are fully selected
        single_items: [], // Array of {id, title, url, type} for individual items
        custom_links: [] // Array of {url, title, keywords}
    };
    
    // Separate enable flags
    var linkingEnable = false;
    var linkingWysiwyg = false;
    
    // Load initial data from PHP if available
    if (typeof sparkplusInitialLinkingPool !== 'undefined') {
        linkingPool = sparkplusInitialLinkingPool;
    }
    if (typeof sparkplusLinkingEnable !== 'undefined') {
        linkingEnable = sparkplusLinkingEnable;
    }
    if (typeof sparkplusLinkingWysiwyg !== 'undefined') {
        linkingWysiwyg = sparkplusLinkingWysiwyg;
    }
    
    // Track which post types have been loaded
    var loadedPostTypes = {};
    
    // ─── Linking Options Checkboxes ───
    
    $('#sparkplus-enable-linking').on('change', function() {
        var isChecked = $(this).prop('checked');
        linkingEnable = isChecked;
        
        // Enable/disable WYSIWYG linking based on main linking checkbox
        var $wysiwygCheckbox = $('#sparkplus-enable-wysiwyg-linking');
        $wysiwygCheckbox.prop('disabled', !isChecked);
        
        // If disabling main linking, also uncheck WYSIWYG linking
        if (!isChecked && $wysiwygCheckbox.prop('checked')) {
            $wysiwygCheckbox.prop('checked', false);
            linkingWysiwyg = false;
        }
        
        console.log('Linking settings updated - enable:', linkingEnable, 'wysiwyg:', linkingWysiwyg);
    });
    
    $('#sparkplus-enable-wysiwyg-linking').on('change', function() {
        linkingWysiwyg = $(this).prop('checked');
        console.log('Linking settings updated - enable:', linkingEnable, 'wysiwyg:', linkingWysiwyg);
    });
    
    // ─── Custom Links Section Toggle ───
    
    $(document).on('click', '.sparkplus-toggle-custom-links', function() {
        var $section = $(this).closest('.sparkplus-custom-links-section');
        var $container = $section.find('.sparkplus-custom-links-container');
        var $button = $(this);
        
        if ($section.hasClass('expanded')) {
            // Collapse
            $section.removeClass('expanded');
            $container.slideUp(200);
            $button.find('.sparkplus-toggle-text').text('Add Links');
        } else {
            // Expand
            $section.addClass('expanded');
            $container.slideDown(200);
            $button.find('.sparkplus-toggle-text').text('Hide');
        }
    });
    
    // ─── Tree Toggle (Expand/Collapse) ───
    
    $(document).on('click', '.sparkplus-tree-toggle', function(e) {
        e.stopPropagation();
        var $folder = $(this).closest('.sparkplus-tree-folder');
        var $children = $folder.find('> .sparkplus-tree-children');
        var postType = $folder.data('post-type');
        
        if ($folder.hasClass('expanded')) {
            // Collapse
            $folder.removeClass('expanded');
            $children.slideUp(200);
        } else {
            // Expand
            $folder.addClass('expanded');
            
            // Load items if not already loaded
            if (!loadedPostTypes[postType]) {
                loadPostTypeItems(postType, $children);
            } else {
                $children.slideDown(200);
            }
        }
    });
    
    // Load items for a post type via AJAX
    function loadPostTypeItems(postType, $container) {
        var $folder = $('.sparkplus-tree-folder[data-post-type="' + postType + '"]');
        var $loading = $folder.find('.sparkplus-tree-loading');
        
        $loading.show();
        
        // Make AJAX request to fetch posts
        $.ajax({
            url: sparkplusSettings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'sparkplus_get_post_type_items',
                nonce: sparkplusSettings.nonce,
                post_type: postType
            },
            success: function(response) {
                $container.empty();
                
                if (response.success && response.data.items) {
                    var items = response.data.items;
                    
                    if (items.length === 0) {
                        $container.html('<div class="sparkplus-tree-empty">No items found.</div>');
                    } else {
                        items.forEach(function(item) {
                            var itemHtml = 
                                '<div class="sparkplus-tree-item" data-item-id="' + item.id + '" data-item-type="' + escapeAttr(postType) + '">' +
                                '<div class="sparkplus-tree-row">' +
                                '<label class="sparkplus-tree-label">' +
                                '<input type="checkbox" class="sparkplus-tree-checkbox sparkplus-item-checkbox" ' +
                                'data-item-id="' + item.id + '" ' +
                                'data-item-type="' + escapeAttr(postType) + '" ' +
                                'data-item-title="' + escapeAttr(item.title) + '" ' +
                                'data-item-url="' + escapeAttr(item.url) + '" />' +
                                '<span class="sparkplus-tree-icon">' +
                                '<span class="dashicons dashicons-media-document"></span>' +
                                '</span>' +
                                '<span class="sparkplus-tree-text">' + escapeHtml(item.title) + '</span>' +
                                '</label>' +
                                '</div>' +
                                '</div>';
                            
                            $container.append(itemHtml);
                        });
                    }
                    
                    loadedPostTypes[postType] = true;
                    $loading.hide();
                    $container.slideDown(200);
                    
                    // Update checkbox states based on current selections
                    updateTreeCheckboxStates();
                } else {
                    $container.html('<div class="sparkplus-tree-empty">Error loading items.</div>');
                    $loading.hide();
                    $container.slideDown(200);
                }
            },
            error: function() {
                $container.empty();
                $container.html('<div class="sparkplus-tree-empty">Error loading items.</div>');
                $loading.hide();
                $container.slideDown(200);
            }
        });
    }
    
    // ─── Post Type Checkbox (Select All) ───
    
    $(document).on('change', '.sparkplus-post-type-checkbox', function() {
        var postType = $(this).data('post-type');
        var isChecked = $(this).prop('checked');
        var $folder = $(this).closest('.sparkplus-tree-folder');
        
        if (isChecked) {
            // Add to post_types array
            if (!linkingPool.post_types.includes(postType)) {
                linkingPool.post_types.push(postType);
            }
            
            // Remove any individual items from this post type
            linkingPool.single_items = linkingPool.single_items.filter(function(item) {
                return item.type !== postType;
            });
            
            // Check all child checkboxes
            $folder.find('.sparkplus-item-checkbox').prop('checked', true);
            
        } else {
            // Remove from post_types array
            linkingPool.post_types = linkingPool.post_types.filter(function(pt) {
                return pt !== postType;
            });
            
            // Uncheck all child checkboxes
            $folder.find('.sparkplus-item-checkbox').prop('checked', false);
        }
        
        console.log('Linking pool updated:', linkingPool);
    });
    
    // ─── Individual Item Checkbox ───
    
    $(document).on('change', '.sparkplus-item-checkbox', function() {
        var itemId = $(this).data('item-id');
        var itemType = $(this).data('item-type');
        var itemTitle = $(this).data('item-title');
        var itemUrl = $(this).data('item-url');
        var isChecked = $(this).prop('checked');
        var $postTypeCheckbox = $('.sparkplus-post-type-checkbox[data-post-type="' + itemType + '"]');
        
        // If entire post type is selected, don't track individual items
        if (linkingPool.post_types.includes(itemType)) {
            if (!isChecked) {
                // User unchecked an item when entire type is selected
                // Remove post type and add all OTHER items
                linkingPool.post_types = linkingPool.post_types.filter(function(pt) {
                    return pt !== itemType;
                });
                
                // Add all checked items except this one
                var $folder = $(this).closest('.sparkplus-tree-folder');
                $folder.find('.sparkplus-item-checkbox').each(function() {
                    var $cb = $(this);
                    if ($cb.prop('checked') && $cb.data('item-id') !== itemId) {
                        var item = {
                            id: $cb.data('item-id'),
                            type: $cb.data('item-type'),
                            title: $cb.data('item-title'),
                            url: $cb.data('item-url')
                        };
                        
                        // Add if not already in array
                        if (!linkingPool.single_items.some(function(i) { 
                            return i.id === item.id && i.type === item.type; 
                        })) {
                            linkingPool.single_items.push(item);
                        }
                    }
                });
                
                // Update post type checkbox state
                updatePostTypeCheckboxState(itemType);
            }
        } else {
            // Working with individual items
            if (isChecked) {
                // Add item
                var item = {
                    id: itemId,
                    type: itemType,
                    title: itemTitle,
                    url: itemUrl
                };
                
                if (!linkingPool.single_items.some(function(i) { 
                    return i.id === item.id && i.type === item.type; 
                })) {
                    linkingPool.single_items.push(item);
                }
                
            } else {
                // Remove item
                linkingPool.single_items = linkingPool.single_items.filter(function(item) {
                    return !(item.id === itemId && item.type === itemType);
                });
            }
            
            // Update post type checkbox state (indeterminate if some but not all selected)
            updatePostTypeCheckboxState(itemType);
        }
        
        console.log('Linking pool updated:', linkingPool);
    });
    
    // Update post type checkbox state based on children
    function updatePostTypeCheckboxState(postType) {
        var $folder = $('.sparkplus-tree-folder[data-post-type="' + postType + '"]');
        var $postTypeCheckbox = $folder.find('> .sparkplus-tree-row .sparkplus-post-type-checkbox');
        var $children = $folder.find('.sparkplus-item-checkbox');
        
        // Check if this post type is fully selected
        if (linkingPool.post_types.includes(postType)) {
            $postTypeCheckbox.prop('checked', true).prop('indeterminate', false);
            return;
        }
        
        // Check if there are single items for this post type in linkingPool
        var singleItemsForType = linkingPool.single_items.filter(function(item) {
            return item.type === postType;
        });
        
        if (singleItemsForType.length === 0) {
            // No items selected
            $postTypeCheckbox.prop('checked', false).prop('indeterminate', false);
            return;
        }
        
        // Some items are selected - show indeterminate
        $postTypeCheckbox.prop('checked', false).prop('indeterminate', true);
        
        // If children are loaded, also check if all are selected
        if ($children.length > 0) {
            var checkedCount = $children.filter(':checked').length;
            
            if (checkedCount === $children.length) {
                // All loaded items are checked - convert to post_type selection
                $postTypeCheckbox.prop('checked', true).prop('indeterminate', false);
                if (!linkingPool.post_types.includes(postType)) {
                    linkingPool.post_types.push(postType);
                }
                linkingPool.single_items = linkingPool.single_items.filter(function(item) {
                    return item.type !== postType;
                });
            }
        }
    }
    
    // Update all checkbox states based on current linking pool
    function updateTreeCheckboxStates() {
        // Update post type checkboxes
        linkingPool.post_types.forEach(function(postType) {
            var $checkbox = $('.sparkplus-post-type-checkbox[data-post-type="' + postType + '"]');
            $checkbox.prop('checked', true).prop('indeterminate', false);
            
            // Check all children if loaded
            var $folder = $('.sparkplus-tree-folder[data-post-type="' + postType + '"]');
            $folder.find('.sparkplus-item-checkbox').prop('checked', true);
        });
        
        // Update individual item checkboxes
        linkingPool.single_items.forEach(function(item) {
            var $checkbox = $('.sparkplus-item-checkbox[data-item-id="' + item.id + '"][data-item-type="' + item.type + '"]');
            $checkbox.prop('checked', true);
        });
        
        // Update indeterminate states
        $('.sparkplus-tree-folder').each(function() {
            var postType = $(this).data('post-type');
            if (!linkingPool.post_types.includes(postType)) {
                updatePostTypeCheckboxState(postType);
            }
        });
    }
    
    // ─── Custom Links ───
    
    // Add custom link
    $('#sparkplus-add-custom-link').on('click', function() {
        var $url = $('#sparkplus-custom-url');
        var $title = $('#sparkplus-custom-title');
        var $keywords = $('#sparkplus-custom-keywords');
        
        var url = $url.val().trim();
        var title = $title.val().trim();
        var keywordsString = $keywords.val().trim();
        
        if (!url) {
            alert('Please enter a URL.');
            $url.focus();
            return;
        }
        
        if (!title) {
            alert('Please enter a title for this link.');
            $title.focus();
            return;
        }
        
        // Parse keywords
        var keywords = [];
        if (keywordsString) {
            keywords = keywordsString.split(',').map(function(k) { return k.trim(); }).filter(function(k) { return k; });
        }
        
        // Add to data structure
        linkingPool.custom_links.push({
            url: url,
            title: title,
            keywords: keywords
        });
        
        // Update UI
        renderCustomLinks();
        
        // Reset form
        $url.val('');
        $title.val('');
        $keywords.val('');
        
        console.log('Linking pool updated:', linkingPool);
    });
    
    // Remove custom link
    $(document).on('click', '.sparkplus-custom-link-remove', function() {
        var index = $(this).data('index');
        linkingPool.custom_links.splice(index, 1);
        renderCustomLinks();
        console.log('Linking pool updated:', linkingPool);
    });
    
    // Render custom links list
    function renderCustomLinks() {
        var $list = $('#sparkplus-custom-links-list');
        $list.empty();
        
        if (linkingPool.custom_links.length === 0) {
            $list.html(
                '<div class="sparkplus-empty-state">' +
                '<span class="dashicons dashicons-info"></span>' +
                '<p>No custom links added yet.</p>' +
                '</div>'
            );
            return;
        }
        
        linkingPool.custom_links.forEach(function(link, index) {
            var keywordsHtml = '';
            if (link.keywords && link.keywords.length > 0) {
                keywordsHtml = '<div class="sparkplus-custom-link-keywords">';
                link.keywords.forEach(function(keyword) {
                    keywordsHtml += '<span class="sparkplus-keyword-tag">' + escapeHtml(keyword) + '</span>';
                });
                keywordsHtml += '</div>';
            }
            
            var itemHtml = 
                '<div class="sparkplus-custom-link-item">' +
                '<div class="sparkplus-custom-link-info">' +
                '<div class="sparkplus-custom-link-title">' + escapeHtml(link.title) + '</div>' +
                '<div class="sparkplus-custom-link-url">' + escapeHtml(link.url) + '</div>' +
                keywordsHtml +
                '</div>' +
                '<button type="button" class="sparkplus-custom-link-remove" data-index="' + index + '">' +
                '<span class="dashicons dashicons-trash"></span>' +
                'Remove' +
                '</button>' +
                '</div>';
            
            $list.append(itemHtml);
        });
    }
    
    // ─── Save Linking Pool ───
    
    $('#sparkplus-save-linking-pool').on('click', function() {
        var $button = $(this);
        var $status = $('#sparkplus-save-status');
        var originalHtml = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
        $status.removeClass('success error').text('');
        
        // Send AJAX request to save linking pool
        $.ajax({
            url: sparkplusSettings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'sparkplus_save_linking_pool',
                nonce: sparkplusSettings.nonce,
                linking_pool: JSON.stringify(linkingPool),
                linking_enable: linkingEnable ? '1' : '0',
                linking_wysiwyg: linkingWysiwyg ? '1' : '0'
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('✓ Linking pool saved successfully!');
                } else {
                    $status.addClass('error').text('✗ ' + (response.data.message || 'Error saving linking pool'));
                }
                $button.prop('disabled', false).html(originalHtml);
                
                setTimeout(function() {
                    $status.fadeOut(400, function() {
                        $(this).removeClass('success error').text('').show();
                    });
                }, 2000);
            },
            error: function() {
                $status.addClass('error').text('✗ Error saving linking pool');
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // ─── Helper Functions ───
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function escapeAttr(text) {
        return String(text).replace(/"/g, '&quot;');
    }
    
    // ─── Initialize on Page Load ───
    
    if ($('.sparkplus-interlinking-container').length > 0) {
        // Update tree and custom links with loaded data
        updateTreeCheckboxStates();
        renderCustomLinks();
        
        // Initialize WYSIWYG checkbox state based on main linking checkbox
        var $enableLinking = $('#sparkplus-enable-linking');
        var $wysiwygLinking = $('#sparkplus-enable-wysiwyg-linking');
        $wysiwygLinking.prop('disabled', !linkingEnable);
    }
    
});
