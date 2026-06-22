gsap.registerPlugin(ScrollTrigger, SplitText);
gsap.config({
    nullTargetWarn: false,
    trialWarn: false
});
/*----  Functions  ----*/
function pbmit_img_animation() {
    const boxes = gsap.utils.toArray('.pbmit-animation-style1,.pbmit-animation-style2,.pbmit-animation-style3,.pbmit-animation-style4,.pbmit-animation-style5,.pbmit-animation-style6,.pbmit-animation-style7');
    boxes.forEach(img => {
        gsap.to(img, {
            scrollTrigger: {
                trigger: img,
                start: "top 70%",
                end: "bottom bottom",
                toggleClass: "active",
                once: true,
            }
        });
    });
}

function getpercentage(x, y, elm) { 
    elm.find('.pbmit-fid-inner').html(y + '/' + x);
    var cal = Math.round((y * 100) / x);
    return cal;
}

function pbmit_title_animation() {

	ScrollTrigger.matchMedia({
		"(min-width: 1025px)": function() {

		var pbmit_var = jQuery('.pbmit-heading, .pbmit-heading-subheading');
		if (!pbmit_var.length) {
			return;
		}
		const quotes = document.querySelectorAll(".pbmit-heading-subheading .pbmit-title, .pbmit-heading .pbmit-title");

			quotes.forEach(quote => {

				//Reset if needed
				if (quote.animation) {
					quote.animation.progress(1).kill();
					quote.split.revert();
				}

				var getclass = quote.closest('.pbmit-heading-subheading, .pbmit-heading').className;
				var animation = getclass.split('animation-');
				if (animation[1] == "style4") return

				quote.split = new SplitText(quote, {
					type: "lines,words,chars",
					linesClass: "split-line"
				});
				gsap.set(quote, { perspective: 400 });

				if (animation[1] == "style1") {
					gsap.set(quote.split.chars, {
						opacity: 0,
						y: "90%",
						rotateX: "-40deg"
					});
				}
				if (animation[1] == "style2") {
					gsap.set(quote.split.chars, {
						opacity: 0,
						x: "50"
					});
				}
				if (animation[1] == "style3") {
					gsap.set(quote.split.chars, {
						opacity: 0,
					});
				}
				quote.animation = gsap.to(quote.split.chars, {
					scrollTrigger: {
						trigger: quote,
						start: "top 90%",
					},
					x: "0",
					y: "0",
					rotateX: "0",
					opacity: 1,
					duration: 1,
					ease: Back.easeOut,
					stagger: .02
				});
			});
		},
	});
}

function pbmit_extend_section() {
	const pbmit_elm = gsap.utils.toArray('.pbmit-extend-animation');
	if (pbmit_elm.length == 0) return	
	ScrollTrigger.matchMedia({
		"(min-width: 1200px)": function() {
			 
			pbmit_elm.forEach(section => {
				let tl = gsap.timeline({
					scrollTrigger: {
						trigger: section,
						start: "top 50%",
						end: "+=400px",
						scrub: 1
					},
					defaults: { ease: "none" }
				});
				tl.fromTo(section, { clipPath: 'inset(0% 5% 0% 5% round 30px)' }, { clipPath: 'inset(0% 0% 0% 0% round 0px)', duration: 1.5 })	
			});			 
		}
	});
}

function pbmit_portfolio4_slider() {
	jQuery(".pbmit-element-portfolio-style-4").each(function() {

		if (typeof Swiper !== 'undefined') {

			var pbmit_port_slide = new Swiper('.swiper-hover-slide-nav', {
				spaceBetween: 0,
				autoplay :false,
				loop:true,
				slidesPerView: '1',
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
				breakpoints	: {
					1025: {
						slidesPerView: '4',
					},
					767: {
						slidesPerView: '3',
					},
					575: {
						slidesPerView: '2',
					},
				},
			});
			var pbmit_hover_fade1 = new Swiper(".pbmit-hover-image-faded", {
				speed: 6000,
				effect: 'fade',
			});
			jQuery('.pbmit-main-hover-faded .swiper-hover-slide-nav .swiper-slide').on('mouseover',function(e) {
				e.preventDefault();
				var myindex = jQuery(this).attr('data-swiper-slide-index');
				pbmit_hover_fade1.slideTo(myindex, 2000, false);
			});
		}
	});		
}

function pbmit_animate_custom_text() {
	jQuery("#js-rotating").Morphext({
		animation: "flipInX",
		speed: 3000,
	});
}

function pbmit_ihbox_move() {

	var pbmit_var = jQuery('.pbmit-move-sofa');
	if (!pbmit_var.length) {
		return;
	}
	ScrollTrigger.matchMedia({
		"(min-width: 1200px)": function() {

			gsap.set(".pbmit-move-sofa", { yPercent:20, })

			gsap.to(".pbmit-move-sofa", {		
				yPercent: -50,
				scrollTrigger: {
					scrub: true,
					start: () => "top top", 
					end:() =>  "bottom top",
					scrub:2
				}
			});
		},
		"(max-width:1200px)": function() {
			ScrollTrigger.getAll().forEach(scrub => scrub.kill(true));
		}
	});
}

function pbmit_sticky() {

	ScrollTrigger.matchMedia({
		"(min-width: 1200px)": function() {
			let pbmit_sticky_container = jQuery(".pbmit-sticky-col");
			let section = pbmit_sticky_container.closest('section');
			if (!section[0]) {
				section = pbmit_sticky_container.closest('.pbmit-sticky-section');
			} 
			let tl = gsap.timeline({
				scrollTrigger: {
					pin: pbmit_sticky_container,
					scrub: 1,
					start: "top top", 
					trigger: section,
					end: () => "+=" + ((section.height() + 150) - window.innerHeight), 
					invalidateOnRefresh: true
				},
				defaults: { ease: "none", duration: 1 }
			});
		},
	}); 
}

var pbmit_active_hover = function() {

	var pbmit_var = jQuery('.pbmit-element-portfolio-style-4,.pbmit-element-static-box-style-2');

	if (!pbmit_var.length) {
		return;
	}

	pbmit_var.each(function() {
		var pbmit_Class = '.swiper-hover-slide-nav .swiper-slide,.pbmit-hover-inner li';
		jQuery(this)
			.find(pbmit_Class).first()
			.addClass('pbmit-active');
		jQuery(this)
			.find(pbmit_Class)
			.on('mouseover', function() {
				jQuery(this).addClass('pbmit-active').siblings().removeClass('pbmit-active');
			});
	});
}


function pbmit_staticbox_hover() {
	var pbmit_var = jQuery('.pbmit-element-static-box-style-1, .pbmit-element-static-box-style-4');
	if (!pbmit_var.length) {
		return;
	}
	pbmit_var.each(function() {
		var pbmit_Class = ' .swiper-static-slide-nav li, .pbmit-hover-inner li, .pbmit-static-box-style-1';
		jQuery(this)
			.find(pbmit_Class).first()
			.addClass('pbmit-active');
		jQuery(this)
			.find(pbmit_Class)
			.on('mouseover', function() {
				jQuery(this).addClass('pbmit-active').siblings().removeClass('pbmit-active');
			});
	});
}

function pbmit_set_tooltip() {
    $('[data-cursor-tooltip]').each(function() {
        var thisele = $(this);
        var thisele_html = thisele.find('.pbminfotech-box-content').html();
        thisele.attr("data-cursor-tooltip", thisele_html);
    });
}

function pbmit_mousehover_tooltip() {

	jQuery("<div id='pbmit-portfolio-cursor'><div class='pbmit-tooltip-content pbminfotech-box-content'></div></div>").appendTo("body");

	var pbmit_text = jQuery('.pbmit-element-portfolio-style-6 .pbminfotech-post-content');
	var pbmit_cursor = jQuery("#pbmit-portfolio-cursor");

	jQuery(document).on('mousemove', function(e) {
		var pbmit_x = e.clientX;
		var pbmit_y = e.clientY;
		pbmit_cursor.css({ "transform": "translate3d(" + pbmit_x + "px, " + pbmit_y + "px, 0px)" });
	})
	tooltiprecall();
}

function tooltiprecall(){
	var pbmit_text = jQuery('.pbmit-element-portfolio-style-6 .pbminfotech-post-content');
	var pbmit_cursor = jQuery("#pbmit-portfolio-cursor");
 
	if (pbmit_text.length) {
		pbmit_text.each(function() {
			var elm = jQuery(this);
			var pbmit_html = elm.find('.pbminfotech-box-content').html();
			elm.on('mouseenter', function() {
				pbmit_cursor.addClass('active').find('.pbmit-tooltip-content').html(pbmit_html);
			}).on('mouseleave', function(e) {
				pbmit_cursor.removeClass('active').find('.pbmit-tooltip-content').html('');
			});
		});
	}
}

var pbmit_vertical_scroll_fix_area = function() {
	if (typeof Swiper !== 'undefined') { 

		var swiper = new Swiper("section .pbmit-column:nth-child(2) .pbmit-vertical-scroller-wrapper", {
			direction		: "vertical",
			createElements	: true,
			slideClass		: 'pbmit-ele',
			centeredSlides: true,
			slidesPerView: 'auto',
			allowTouchMove: false,
			loop			: true,
			autoplay		: { 
				delay: 100,
			},
			speed			: 3000,
			spaceBetween  : 30,
			breakpoints: {		
				768: {
					spaceBetween  : 30,
					slidesPerView	: 2,
				},
				576: {
					slidesPerView	: 2,	
				},
			}
		});
		var swiper = new Swiper(".pbm-scroll-reverce .pbmit-vertical-scroller-wrapper", {
			direction		: "vertical",
			createElements	: true,
			slideClass		: 'pbmit-ele',
			slidesPerView	: 1,
			centeredSlides: true,
			allowTouchMove: false,
			loop			: true,
			autoplay: {
				delay: 100,
				reverseDirection: true,
				disableOnInteraction: false,
			},
			speed			: 3000,
			spaceBetween  	: 30,
			breakpoints: {		
				768: {
					spaceBetween  : 30,
					slidesPerView	: 2,
				},
				576: {
					slidesPerView	: 2,	
				},
			}
		});
	}
}

var pbmit_staticbox_hover_slide = function() {
	if (typeof Swiper !== 'undefined') {
		
		var pbmit_hover_image = new Swiper(".pbmit-element-static-box-style-2 .pbmit-static-image", {
			speed: 600,
			effect: 'fade',
		});
		var pbmit_hover_image1 = new Swiper(".pbmit-element-static-box-style-4 .pbmit-static-image", {
			speed: 600,
			effect: 'fade',
		});
		var pbmit_hover_desc = new Swiper(".pbmit-element-static-box-style-4 .pbmit-static-desc", {
			speed: 600,
			effect: 'fade',
        });

		jQuery('.swiper-static-slide-nav li').on('mouseover', function(e) {
		e.preventDefault();
			var myindex = jQuery(this).index();		
	
            if (jQuery(this).closest('.pbmit-element-static-box-style-4').length > 0) {       
                pbmit_hover_desc.slideTo(myindex, 600);   // Slide description in Style 4
                pbmit_hover_image1.slideTo(myindex, 600); // Slide image in Style 4
            } else {
                // If not Style 4, skip description or handle differently
                pbmit_hover_image.slideTo(myindex, 600, false);  // Slide only image for Style 2
            }
           
		});
	}
}

ScrollTrigger.matchMedia({
    "(max-width: 1200px)": function() {
        ScrollTrigger.getAll().forEach(t => t.kill());
    }
});

// on ready
jQuery(document).ready(function() {
	pbmit_title_animation();
	pbmit_portfolio4_slider();
	pbmit_ihbox_move();
	pbmit_active_hover();
	pbmit_staticbox_hover();
	pbmit_staticbox_hover_slide();

});

// on resize
jQuery(window).resize(function() {
	pbmit_title_animation();
	pbmit_img_animation();
});

// on load
jQuery(window).on('load', function(){
    pbmit_extend_section();
	pbmit_img_animation();
	pbmit_sticky();
	pbmit_animate_custom_text();
	pbmit_set_tooltip();
	pbmit_mousehover_tooltip();
	pbmit_vertical_scroll_fix_area();
	
	jQuery('[data-magnetic]').each(function() { new Magnetic(this); });
	gsap.delayedCall(1, () =>
		ScrollTrigger.getAll().forEach((t) => {
			t.refresh();
		})
	);	
	
	setTimeout(cleaning, 500);
	function cleaning(){
		ScrollTrigger.refresh(); 
	}
});