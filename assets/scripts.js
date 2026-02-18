/**
 * Share2AI – Front-end script.
 *
 * Builds the prompt dynamically using the current page URL and opens
 * the selected AI tool in a new tab.
 */
(function () {
	'use strict';

	if ( typeof share2aiData === 'undefined' ) {
		return;
	}

	var pageUrl   = window.location.href;
	var siteUrl   = share2aiData.siteUrl;
	var tools     = share2aiData.tools;

	/**
	 * Build the prompt string for the current page.
	 */
	function share2ai_buildPrompt() {
		var template = share2aiData.promptTemplate;
		return template
			.replace( '%page_url%', pageUrl )
			.replace( '%site_url%', siteUrl )
			.replace( '%page_title%', share2aiData.pageTitle );
	}

	/**
	 * Update all button hrefs with the current page URL prompt.
	 * Useful if the URL changes (e.g. query params added by JS).
	 */
	function share2ai_updateButtons() {
		var prompt  = share2ai_buildPrompt();
		var encoded = encodeURIComponent( prompt );

		var buttons = document.querySelectorAll( '.share2ai-btn' );
		buttons.forEach( function ( btn ) {
			var tool = btn.getAttribute( 'data-tool' );
			if ( tool && tools[ tool ] ) {
				btn.href = tools[ tool ].replace( '%s', encoded );
			}
		});
	}

	/**
	 * Initialize tooltips for floating panel buttons.
	 */
	function share2ai_initializeTooltips() {
		var floatingPanel = document.querySelector( '.share2ai-floating-panel' );
		if ( ! floatingPanel ) {
			return;
		}

		var buttons = floatingPanel.querySelectorAll( '.share2ai-btn' );
		buttons.forEach( function ( btn ) {
			var tooltipText = btn.getAttribute( 'data-tooltip' );
			if ( ! tooltipText ) {
				return;
			}

			// Create tooltip element
			var tooltip = document.createElement( 'div' );
			tooltip.className = 'share2ai-tooltip';
			tooltip.innerHTML = '<div class="share2ai-tooltip-arrow"></div>' + tooltipText;
			document.body.appendChild( tooltip );

			// Position and show/hide tooltip
			btn.addEventListener( 'mouseenter', function () {
				share2ai_showTooltip( btn, tooltip, floatingPanel );
			});

			btn.addEventListener( 'mouseleave', function () {
				share2ai_hideTooltip( tooltip );
			});
		});
	}

	/**
	 * Show tooltip and position it based on panel location.
	 */
	function share2ai_showTooltip( btn, tooltip, panel ) {
		var btnRect = btn.getBoundingClientRect();
		var panelPos = panel.className.includes( 'share2ai-floating-left' ) ? 'left' : 'right';
		var viewportWidth = window.innerWidth;

		tooltip.classList.add( 'active' );

		// Position based on panel location
		if ( 'left' === panelPos ) {
			// Position to the right of button
			tooltip.classList.remove( 'top', 'left', 'right' );
			tooltip.classList.add( 'right' );
			tooltip.style.left = ( btnRect.right + 16 ) + 'px';
			tooltip.style.top = ( btnRect.top + btnRect.height / 2 - tooltip.offsetHeight / 2 ) + 'px';
		} else {
			// Position to the left of button
			tooltip.classList.remove( 'top', 'left', 'right' );
			tooltip.classList.add( 'left' );
			tooltip.style.right = ( viewportWidth - btnRect.left + 16 ) + 'px';
			tooltip.style.top = ( btnRect.top + btnRect.height / 2 - tooltip.offsetHeight / 2 ) + 'px';
		}

		// Prevent overflow
		setTimeout( function () {
			var tooltipRect = tooltip.getBoundingClientRect();
			if ( tooltipRect.left < 0 ) {
				tooltip.style.left = '10px';
			}
			if ( tooltipRect.right > viewportWidth ) {
				tooltip.style.right = '10px';
				tooltip.style.left = 'auto';
			}
		}, 0 );
	}

	/**
	 * Hide tooltip.
	 */
	function share2ai_hideTooltip( tooltip ) {
		tooltip.classList.remove( 'active' );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		share2ai_updateButtons();
		share2ai_initializeTooltips();
	});
})();
