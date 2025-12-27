(function($){
    'use strict';

    $(document).ready(function(){
        if (typeof productFilterAjax === 'undefined') {
            console.warn('Product Filter: productFilterAjax is not defined. AJAX will not work.');
        }

        // Store initial loop grid state for each widget
        $('.product-filter-widget').each(function(){
            var $widget = $(this);
            var containerSelector = $widget.data('container');
            var $container = $(containerSelector);
            
            if ($container.length) {
                // Find the loop container
                var $loopContainer = $container.hasClass('elementor-loop-container') 
                    ? $container 
                    : $container.find('.elementor-loop-container').first();
                
                if ($loopContainer.length) {
                    // Store the initial HTML of the loop container
                    $widget.data('initial-html', $loopContainer.html());
                    console.log('Stored initial loop HTML for widget');
                }
            }
        });

        // Delegated handler for dynamically rendered widgets
        $(document).on('change', '.product-filter-widget .product-filter-checkbox', function(){
            var $cb = $(this);
            var $thisWidget = $cb.closest('.product-filter-widget');

            var selectedCategories = [];
            $thisWidget.find('.product-filter-checkbox:checked').each(function(){
                selectedCategories.push($(this).val());
            });

            var $thisLoading = $thisWidget.find('.product-filter-loading');
            $thisLoading.show();

            var containerSelectorLocal = $thisWidget.data('container');
            var $containerLocal = $(containerSelectorLocal);
            if ($containerLocal.length && !$containerLocal.is('ul.products')) {
                var $foundLocal = $containerLocal.find('ul.products').first();
                if ($foundLocal.length) $containerLocal = $foundLocal;
            }
            if (!$containerLocal.length) {
                var $foundGlobalLocal = $('ul.products').first();
                if ($foundGlobalLocal.length) $containerLocal = $foundGlobalLocal;
            }

            // If no categories are selected, restore initial state
            if (selectedCategories.length === 0) {
                console.log('Product Filter: No categories selected, restoring initial state');
                var initialHtml = $thisWidget.data('initial-html');
                
                var $loopContainer = null;
                if ($containerLocal && $containerLocal.length) {
                    if ($containerLocal.hasClass('elementor-loop-container')) {
                        $loopContainer = $containerLocal;
                    } else {
                        $loopContainer = $containerLocal.find('.elementor-loop-container').first();
                    }
                }

                if ($loopContainer && $loopContainer.length && initialHtml) {
                    $loopContainer.html(initialHtml);
                    $thisLoading.hide();
                    return;
                }
            }

            var templateIdLocal = null;
            try {
                var $widgetRootLocal = $containerLocal.closest('[data-settings]').first();
                if ($widgetRootLocal.length) {
                    var settingsRawLocal = $widgetRootLocal.attr('data-settings');
                    if (settingsRawLocal) {
                        var settingsLocal = JSON.parse(settingsRawLocal);
                        if (settingsLocal && settingsLocal.template_id) templateIdLocal = settingsLocal.template_id;
                    }
                }
            } catch (e) {
                // ignore
            }

            if (typeof productFilterAjax === 'undefined') {
                console.error('Product Filter: missing productFilterAjax object; aborting request.');
                $thisLoading.hide();
                return;
            }

            console.debug('Product Filter: sending AJAX', selectedCategories, templateIdLocal, containerSelectorLocal);

            $.ajax({
                url: productFilterAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'filter_products',
                    categories: selectedCategories,
                    template_id: templateIdLocal,
                    container_selector: containerSelectorLocal,
                    nonce: productFilterAjax.nonce
                },
                success: function(response){
                    console.log('AJAX response received:', response.success);
                    if (response.success) {
                        var html = response.data.html || '';
                        console.log('HTML length:', html.length);
                        var $widgetRoot = $thisWidget.closest('[data-widget_type], [data-settings]').first();
                        var $parsed = $('<div>').append($.parseHTML(html, document, true));

                        // Extract ALL style tags from response and inject them
                        var $allStyles = $parsed.find('style');
                        console.log('Found styles in response:', $allStyles.length);
                        if ($allStyles.length > 0) {
                            var $headOrWidget = $widgetRoot.length ? $widgetRoot : $('head');
                            $allStyles.each(function(){
                                $headOrWidget.before($(this).clone());
                            });
                            console.log('Styles injected into page');
                        }

                        var $newItems = $parsed.find('[data-elementor-type="loop-item"]').length ? $parsed.find('[data-elementor-type="loop-item"]') : $parsed.find('li.product');
                        console.log('Loop items found:', $newItems.length);

                        var $elementorContainer = null;
                        if ($containerLocal && $containerLocal.length && $containerLocal.hasClass('elementor-loop-container')) {
                            $elementorContainer = $containerLocal;
                            console.log('Case 1: Using $containerLocal as loop-container');
                        } else if ($containerLocal && $containerLocal.length) {
                            // Search for .elementor-loop-container INSIDE the container selector (.testgrid, etc)
                            var $found = $containerLocal.find('.elementor-loop-container').first();
                            if ($found.length) {
                                $elementorContainer = $found;
                                console.log('Case 2: Found .elementor-loop-container inside $containerLocal (.testgrid)');
                            }
                        } else if ($widgetRoot && $widgetRoot.length) {
                            var $found = $widgetRoot.find('.elementor-loop-container').first();
                            if ($found.length) {
                                $elementorContainer = $found;
                                console.log('Case 3: Found .elementor-loop-container inside filter widget');
                            }
                        }
                        console.log('$elementorContainer found:', !!$elementorContainer, '$containerLocal:', $containerLocal.length);

                        if ($elementorContainer && $elementorContainer.length) {
                            console.log('Updating existing container with', $newItems.length, 'items');
                            $elementorContainer.html('');
                            if ($newItems && $newItems.length) $elementorContainer.append($newItems);
                            else $elementorContainer.html(html);
                        } else if ($containerLocal && $containerLocal.length && $containerLocal.is('ul.products')) {
                            $containerLocal.html('');
                            if ($newItems && $newItems.length) $containerLocal.append($newItems);
                            else $containerLocal.html(html);
                        } else if ($parsed.find('.elementor-loop-container').length) {
                            var $returnedContainer = $parsed.find('.elementor-loop-container').first();
                            if ($widgetRoot && $widgetRoot.length) {
                                var $existing = $widgetRoot.find('.elementor-loop-container').first();
                                if ($existing.length) $existing.replaceWith($returnedContainer);
                                else $widgetRoot.append($returnedContainer);
                            }
                        } else {
                            if ($newItems && $newItems.length) {
                                var $wrapper = $('<div/>', {'class':'elementor-loop-container elementor-grid','role':'list'});
                                $wrapper.append($newItems);
                                if ($widgetRoot && $widgetRoot.length) {
                                    var $existing = $widgetRoot.find('.elementor-loop-container').first();
                                    if ($existing.length) $existing.replaceWith($wrapper);
                                    else $widgetRoot.append($wrapper);
                                } else if ($containerLocal && $containerLocal.length) {
                                    $containerLocal.replaceWith($wrapper);
                                } else {
                                    var $global = $('ul.products, .elementor-loop-container').first();
                                    if ($global.length) $global.replaceWith($wrapper);
                                }
                            } else {
                                if ($containerLocal && $containerLocal.length) $containerLocal.html(html);
                                else {
                                    var $global = $('ul.products, .elementor-loop-container').first();
                                    if ($global.length) $global.html(html);
                                }
                            }
                        }
                    } else {
                        console.error('AJAX error:', response.data);
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX request failed:', error);
                },
                complete: function(){
                    $thisLoading.hide();
                }
            });
        });

        // Delegated handler for reset link click
        $(document).on('click', '.product-filter-reset-link', function(e){
            e.preventDefault();

            var $resetLink = $(this);
            var $thisWidget = $resetLink.closest('.product-filter-widget');

            // Uncheck all checkboxes in this widget
            $thisWidget.find('.product-filter-checkbox').prop('checked', false);

            // Show loading indicator
            var $thisLoading = $thisWidget.find('.product-filter-loading');
            $thisLoading.show();

            var containerSelector = $thisWidget.data('container');
            var $container = $(containerSelector);
            
            if (!$container.length) {
                $container = $('ul.products').first();
            }

            // Find the loop container
            var $loopContainer = null;
            if ($container.length) {
                if ($container.hasClass('elementor-loop-container')) {
                    $loopContainer = $container;
                } else {
                    $loopContainer = $container.find('.elementor-loop-container').first();
                }
            }

            // Get the initial HTML that was stored
            var initialHtml = $thisWidget.data('initial-html');

            if ($loopContainer && $loopContainer.length && initialHtml) {
                // Restore the initial HTML immediately
                console.log('Restoring initial loop grid state');
                $loopContainer.html(initialHtml);
                $thisLoading.hide();
            } else {
                // Fallback: send AJAX to reload all products
                console.log('Initial HTML not found, falling back to AJAX reload');
                
                var templateIdLocal = null;
                try {
                    var $widgetRootLocal = $container.closest('[data-settings]').first();
                    if ($widgetRootLocal.length) {
                        var settingsRawLocal = $widgetRootLocal.attr('data-settings');
                        if (settingsRawLocal) {
                            var settingsLocal = JSON.parse(settingsRawLocal);
                            if (settingsLocal && settingsLocal.template_id) templateIdLocal = settingsLocal.template_id;
                        }
                    }
                } catch (e) {
                    // ignore
                }

                if (typeof productFilterAjax === 'undefined') {
                    console.error('Product Filter: missing productFilterAjax object; aborting reset.');
                    $thisLoading.hide();
                    return;
                }

                console.debug('Product Filter: Reset link clicked, sending AJAX with empty categories', templateIdLocal);

                // Send AJAX with empty categories array to load all products
                $.ajax({
                    url: productFilterAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'filter_products',
                        categories: [],
                        template_id: templateIdLocal,
                        container_selector: containerSelector,
                        nonce: productFilterAjax.nonce
                    },
                    success: function(response){
                        console.log('Reset AJAX response received:', response.success);
                        if (response.success) {
                            var html = response.data.html || '';
                            var $widgetRoot = $thisWidget.closest('[data-widget_type], [data-settings]').first();
                            var $parsed = $('<div>').append($.parseHTML(html, document, true));

                            // Extract ALL style tags from response and inject them
                            var $allStyles = $parsed.find('style');
                            if ($allStyles.length > 0) {
                                var $headOrWidget = $widgetRoot.length ? $widgetRoot : $('head');
                                $allStyles.each(function(){
                                    $headOrWidget.before($(this).clone());
                                });
                            }

                            var $newItems = $parsed.find('[data-elementor-type="loop-item"]').length ? $parsed.find('[data-elementor-type="loop-item"]') : $parsed.find('li.product');

                            var $elementorContainer = null;
                            if ($container && $container.length && $container.hasClass('elementor-loop-container')) {
                                $elementorContainer = $container;
                            } else if ($container && $container.length) {
                                var $found = $container.find('.elementor-loop-container').first();
                                if ($found.length) {
                                    $elementorContainer = $found;
                                }
                            } else if ($widgetRoot && $widgetRoot.length) {
                                var $found = $widgetRoot.find('.elementor-loop-container').first();
                                if ($found.length) {
                                    $elementorContainer = $found;
                                }
                            }

                            if ($elementorContainer && $elementorContainer.length) {
                                $elementorContainer.html('');
                                if ($newItems && $newItems.length) $elementorContainer.append($newItems);
                                else $elementorContainer.html(html);
                            } else if ($container && $container.length && $container.is('ul.products')) {
                                $container.html('');
                                if ($newItems && $newItems.length) $container.append($newItems);
                                else $container.html(html);
                            } else {
                                if ($newItems && $newItems.length) {
                                    var $wrapper = $('<div/>', {'class':'elementor-loop-container elementor-grid','role':'list'});
                                    $wrapper.append($newItems);
                                    if ($widgetRoot && $widgetRoot.length) {
                                        var $existing = $widgetRoot.find('.elementor-loop-container').first();
                                        if ($existing.length) $existing.replaceWith($wrapper);
                                        else $widgetRoot.append($wrapper);
                                    } else if ($container && $container.length) {
                                        $container.replaceWith($wrapper);
                                    } else {
                                        var $global = $('ul.products, .elementor-loop-container').first();
                                        if ($global.length) $global.replaceWith($wrapper);
                                    }
                                } else {
                                    if ($container && $container.length) $container.html(html);
                                    else {
                                        var $global = $('ul.products, .elementor-loop-container').first();
                                        if ($global.length) $global.html(html);
                                    }
                                }
                            }
                        } else {
                            console.error('Reset AJAX error:', response.data);
                        }
                    },
                    error: function(xhr, status, error){
                        console.error('Reset AJAX request failed:', error);
                    },
                    complete: function(){
                        $thisLoading.hide();
                    }
                });
            }
        });
    });
})(jQuery);
