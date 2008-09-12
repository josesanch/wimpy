/*
 * image gallery using jQuery and Interface - http://www.getintothis.com
 * 
 * note: this library depends on jQuery (http://www.jquery.com) and
 * Interface (http://interface.eyecon.ro)
 *
 * Copyright (c) 2006 Ramin Bozorgzadeh
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LECENSE.txt) linceses.
 */

$.fn.gallery = function(options) {
    var self = this;

    self.options = {
        imageHolderClass: 'image_holder',
        reflection: true,
        thumbs: true,
        thumbsDimension: 50,
        thumbsBorderWidth: 2,
        naviconWidth: 50,
        triggerHeight: 25
    };

    // make sure to check if options are given!
    if(options) {
        $.extend(self.options, options);
    }

    this.each(function() {
        var g = this;
        var reflection_height = (self.options.reflection) ? 120 : 0;

        g.image_holder = $('#' + g.id + ' .' + self.options.imageHolderClass)[0];
        g.swapping_image = false;


        $(g).append(
            '<div class="back_next">' +
                '<a href="" class="back"><span>&#171; back</span></a>' +
                '<a href="" class="next"><span>next &#187;</span></a>' +
            '</div>'
        );
        g.nav = $(g).find('div.back_next');

        if(self.options.thumbs) {
            $(g).append('<div class="thumbs"></div>');
            g.thumbs = $('#' + g.id + ' .thumbs')[0];
            g.thumbs.open = ($(g.thumbs).css('display') == 'block');
        }


        g.init_gallery = function(imgs) {
            g.imgs = imgs;
            g.image = $(g.image_holder).find('img')[0];
            g.image_index = 0;

            $(g.image_holder).height($(g.image).offset().height + reflection_height + 'px');


            g.swap_image(g.imgs[0], function() {
                g.init_nav();
                g.init_thumbs();
            });
        } // g.init_gallery



        g.init_nav = function() {
            $(g.nav).find('a').height($(g.image).offset().height + 'px');
            $(g.nav).find('a').unbind();
            // get rid of annoying dotted border on focus
            // for hyperlinks
            $(g.nav).find('a').focus(function() {
                this.hideFocus = true;
            });
            $(g.nav).find('a.next').click(function() {
                if(g.image_index < g.imgs.length - 1 && !g.swapping_image) {
                    g.image_index = g.image_index + 1;

                    $(g.thumbs).find('a.active').removeClass('active');
                    $($(g.thumbs).find('a')[g.image_index]).addClass('active');

                    g.swap_image(g.imgs[g.image_index]);
                }
                return false;
            });
            $(g.nav).find('a.back').click(function() {
                if(g.image_index > 0 && !g.swapping_image) {
                    g.image_index = g.image_index - 1;

                    $(g.thumbs).find('a.active').removeClass('active');
                    $($(g.thumbs).find('a')[g.image_index]).addClass('active');

                    g.swap_image(g.imgs[g.image_index]);
                }
                return false;
            });
            $(g.nav).show();
        } //g.init_nav


        
        /*
         * funtion to swap images
         */
        g.swap_image = function(new_img_src, callback) {
            var orig_img_width = g.image.width;
            var new_img = new Image();

            g.swapping_image = true;
            
            $(new_img).addClass('swap');
            $(new_img).load(function() {

                $(g.image_holder).animate({
                    height: this.height + reflection_height
                }, 'fast', function() {
                    $(this).append(new_img);

                    // remove the reflection
                    if(self.options.reflection) {
                        if($(g.image_holder).find('canvas').size() > 0) {
                            $(g.image_holder).find('canvas').fadeOut('normal', function() {
                                Reflection.remove(g.image);
                            });
                        } else {
                            Reflection.remove(g.image);
                        }
                        
                    }
                    $(g.image).fadeOut('normal', function() {
                        $(this).remove();
                        g.image = new_img;
                        $(new_img).removeClass('swap');

                        // add reflection the new image
                        if(self.options.reflection) {
                            Reflection.add(new_img);
                            //$(g.image_holder).find('canvas').hide().fadeIn('normal');
                        }
                    });

                    $(new_img).fadeIn('normal', function() {
                        // once image loads, execute
                        // callback function if defined
                        if(typeof callback != 'undefined') {
                            setTimeout(function() {
                                callback(g.image); 
                            }, 500);
                        }

                        g.swapping_image = false;
                    });
                });
            });

            new_img.src = new_img_src;

            return;
        }

        
        g.init_thumbs = function() {
            if(!self.options.thumbs) return;

            $(g.thumbs).html('<div class="loading_msg">loading <strong>' + 1 + 
                '</strong> of <strong>' + g.imgs.length + 
                '</strong> images...</div>');

            $(g.thumbs).left(self.options.naviconWidth + 5 + 'px');
            $(g.thumbs).width($(g.image_holder).offset().width - 
                (2 * self.options.naviconWidth) - 
                (2 * parseInt($(g.thumbs).css('padding-left'))) - 10 + 'px');

            var fadeInTimer = setTimeout(function() {
                if(!g.thumbs.open) {
                    $(g.thumbs).fadeIn(500, function() {
                        g.thumbs.open = true;
                    });
                }
            }, 300)

            var fadeOutTimer = setTimeout(function() {
                if(g.thumbs.open) {
                    $(g.thumbs).fadeOut(500, function() {
                        g.thumbs.open = false;
                    });
                }
            }, 5000);
            
            var triggerHeight = $(g.image).offset().top + self.options.triggerHeight;

            $(g.nav).mousemove(function(e) {
                if(e.clientY < triggerHeight &&
                        e.clientX > $(g).offset().left + self.options.naviconWidth &&
                        e.clientX < $(g).offset().left + $(g).offset().width - self.options.naviconWidth &&
                        !g.thumbs.open) {
                    if(fadeInTimer) {
                        clearTimeout(fadeInTimer);
                    }
                    g.thumbs.open = true;
                    $(g.thumbs).fadeIn(500, function() {
                    });
                    return false;
                }
            });

            $(g.thumbs).unbind();
            $(g.thumbs).hover(
                function() {
                    if(fadeOutTimer) {
                        clearTimeout(fadeOutTimer);
                    }
                    $(this).addClass('hover');
                    return false;
                }, function() {
                    fadeOutTimer = setTimeout(function() {
                        if(g.thumbs.open) {
                            $(g.thumbs).fadeOut(500, function() {
                                g.thumbs.open = false;
                            });
                        }
                    }, 100);
                    $(this).removeClass('hover');
                    return false;
                }
            );


            $(g.thumbs).append('<ul class="clearfix"></ul>');

            g.load_thumb($(g.thumbs).find('ul')[0], 0);
        };
        // g.init_thumbs


        g.load_thumb = function(el, index) {
            $(el).append(
                '<li>' +
                    '<a href="' + g.imgs[index] + '" class="' + ((index == 0) ? 'active' : '') + '" style="width: ' +
                            self.options.thumbsDimension + 'px; height: ' +
                            self.options.thumbsDimension + 'px; line-height: ' +
                            self.options.thumbsDimension + 'px">' +
                    '</a>' +
                '</li>'   
            );

            
            var tn = new Image();

            $(tn).load(function() {
                var a = $($(el).find('li')[index]).find('a')[0];

                $(a).append(this);

                $(a).click(function() {
                    if(g.swapping_image) return false;

                    $(g.thumbs).find('a.active').removeClass('active');
                    $(this).addClass('active');

                    g.image_index = index;

                    g.swap_image($(this).attr('href'));
                    
                    return false;
                });


                if((index + 1) < g.imgs.length) {
                    g.load_thumb(el, (index + 1));
                    $($(g.thumbs).find('div.loading_msg strong')[0]).html(index + 2);
                } else {
                    $(g.thumbs).find('div.loading_msg').html('finished loading <strong>' + g.imgs.length + '</strong> images');
                }
                
                
            });
            tn.src = g.imgs[index].replace('.jpg','_s.jpg');
        };
        // g.load_thumb

    });


    if(this.size() == 1) {
        return this[0];
    } else {
        return this;
    }
};