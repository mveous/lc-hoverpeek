jQuery(function ($) {

	let lchoPopup = null;
	let lchoHideTimer = null;
	let lchoHoverTimer = null;
	let lchoActiveXHR = null;

	window.lchoCache = window.lchoCache || {};

	const lchoSelector = 'a[data-lcho-post], a[data-lcho-url]';


	function lchoRemovePopup() {

		if (lchoPopup) {
			lchoPopup.remove();
			lchoPopup = null;
		}

		if (lchoActiveXHR) {
			lchoActiveXHR.abort();
			lchoActiveXHR = null;
		}
	}


	function lchoScheduleHide() {

		if (lchoActiveXHR) {
			lchoActiveXHR.abort();
			lchoActiveXHR = null;
		}

		lchoHideTimer = setTimeout(lchoRemovePopup, 250);
	}


	function lchoSmartPosition(popup, x, y) {

		const padding = 12;

		const popupWidth = popup.outerWidth();
		const popupHeight = popup.outerHeight();

		const winWidth = $(window).width();
		const winHeight = $(window).height();

		let left = x + 15;
		let top = y + 15;

		if (left + popupWidth + padding > winWidth) {
			left = x - popupWidth - 15;
		}

		if (left < padding) {
			left = padding;
		}

		if (top + popupHeight + padding > winHeight) {
			top = y - popupHeight - 15;
		}

		if (top < padding) {
			top = padding;
		}

		popup.css({ left, top });
	}


	function lchoShowPopup(data, x, y) {

		lchoRemovePopup();

		lchoPopup = $(`
			<div class="lcho-popup">
				<div class="lcho-content">
					<div class="lcho-image" style="display: none;">
						<img src="" alt="">
					</div>
					<div class="lcho-text">
						<h4></h4>
						<p></p>
						<a href="" class="lcho-more">
							Learn more →
						</a>
					</div>
				</div>
			</div>
		`).appendTo('body');

		if (data.image) {
			lchoPopup.find('.lcho-image img').attr('src', data.image);
			lchoPopup.find('.lcho-image').show();
		}
		lchoPopup.find('.lcho-text h4').text(data.title || '');
		lchoPopup.find('.lcho-text p').text(data.excerpt || '');
		lchoPopup.find('.lcho-text a.lcho-more').attr('href', data.link || '');

		lchoSmartPosition(lchoPopup, x, y);

		lchoPopup.on('mouseenter', () => clearTimeout(lchoHideTimer));
		lchoPopup.on('mouseleave', lchoScheduleHide);
	}


	/* Hover preview */

	$(document).on('mouseenter', lchoSelector, function (e) {

		clearTimeout(lchoHideTimer);

		const $link = $(this);

		const postID = $link.data('lcho-post');
		const url = $link.data('lcho-url');

		if (!postID && !url) return;

		const key = postID ? 'post-' + postID : 'url-' + url;

		const x = e.pageX;
		const y = e.pageY;

		/* Show instantly if cached */

		if (lchoCache[key]) {
			lchoShowPopup(lchoCache[key], x, y);
			return;
		}

		/* Fallback AJAX */

		lchoHoverTimer = setTimeout(function () {

			if (lchoActiveXHR) {
				lchoActiveXHR.abort();
			}

			lchoActiveXHR = $.post(
				lcho.ajax_url,
				{
					action: 'lcho_preview',
					post_id: postID || 0,
					url: url || '',
					nonce: lcho.nonce
				},
				function (res) {

					lchoActiveXHR = null;

					if (!res.success) return;

					lchoCache[key] = res.data;

					lchoShowPopup(res.data, x, y);

				}
			);

		}, 120);

	});


	$(document).on('mouseleave', lchoSelector, function () {

		clearTimeout(lchoHoverTimer);
		lchoScheduleHide();

	});


	$(document).on('scroll resize', lchoRemovePopup);


	/* Batch prefetch */

	function lchoBatchPrefetch() {

		const links = [];

		$('a[data-lcho-post], a[data-lcho-url]').each(function () {

			const postID = $(this).data('lcho-post');
			const url = $(this).data('lcho-url');

			if (postID) {
				links.push({ post_id: postID, url: '' });
			}

			if (url) {
				links.push({ post_id: 0, url: url });
			}

			if (links.length >= 10) {
				return false;
			}

		});

		if (!links.length) return;

		$.post(
			lcho.ajax_url,
			{
				action: 'lcho_batch',
				links: links,
				nonce: lcho.nonce
			},
			function (res) {

				if (!res.success) return;

				res.data.forEach(function (item) {

					const key = item.post_id
						? 'post-' + item.post_id
						: 'url-' + item.link;

					lchoCache[key] = item;

				});

			}
		);

	}


	$(window).on('load', function () {

		setTimeout(lchoBatchPrefetch, 800);

	});

});