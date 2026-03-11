/**
 * TCG Theme — Client-side live search for ygo_card.
 * Intercepts the Bricks Filter-Search input and searches preloaded cards locally.
 */
(function() {
	'use strict';

	var cards = window.tcgLiveSearch ? window.tcgLiveSearch.cards : [];
	var cardBaseUrl = window.tcgLiveSearch ? window.tcgLiveSearch.cardBaseUrl : '/carta/';
	var minChars = 3;
	var maxResults = 8;
	var debounceMs = 200;
	var debounceTimer = null;
	var activeIndex = -1;

	// Find the Bricks search input inside the filter-search element.
	var input = document.querySelector('.brxe-filter-search input[type="text"], .brxe-filter-search input[type="search"]');
	if (!input || !cards.length) return;

	// Create dropdown container.
	var dropdown = document.createElement('div');
	dropdown.className = 'tcg-live-search-dropdown';
	dropdown.style.display = 'none';

	// Insert dropdown right after the input's parent (the Bricks filter element).
	var wrapper = input.closest('.brxe-filter-search');
	if (wrapper) {
		wrapper.style.position = 'relative';
		wrapper.appendChild(dropdown);
	} else {
		input.parentNode.appendChild(dropdown);
	}

	// Prevent Bricks from handling this input (stop AJAX).
	input.addEventListener('input', function(e) {
		e.stopPropagation();
		clearTimeout(debounceTimer);
		activeIndex = -1;

		var term = input.value.trim().toLowerCase();
		if (term.length < minChars) {
			hideDropdown();
			return;
		}

		debounceTimer = setTimeout(function() {
			var results = search(term);
			render(results, term);
		}, debounceMs);
	}, true);

	// Prevent Bricks form submission / AJAX on enter.
	input.addEventListener('keydown', function(e) {
		var items = dropdown.querySelectorAll('.tcg-live-search-item');

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			activeIndex = Math.min(activeIndex + 1, items.length - 1);
			updateActive(items);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			activeIndex = Math.max(activeIndex - 1, 0);
			updateActive(items);
		} else if (e.key === 'Enter') {
			e.preventDefault();
			e.stopPropagation();
			if (activeIndex >= 0 && items[activeIndex]) {
				var link = items[activeIndex].querySelector('a');
				if (link) window.location.href = link.href;
			}
		} else if (e.key === 'Escape') {
			hideDropdown();
			input.blur();
		}
	}, true);

	// Also block the native form submit.
	var form = input.closest('form');
	if (form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			e.stopPropagation();
		}, true);
	}

	// Close dropdown on click outside.
	document.addEventListener('click', function(e) {
		if (!wrapper.contains(e.target)) {
			hideDropdown();
		}
	});

	function search(term) {
		var matches = [];
		for (var i = 0; i < cards.length && matches.length < maxResults; i++) {
			if (cards[i].label.toLowerCase().indexOf(term) !== -1) {
				matches.push(cards[i]);
			}
		}
		return matches;
	}

	function render(results, term) {
		if (!results.length) {
			dropdown.innerHTML = '<div class="tcg-live-search-empty">No se encontraron cartas</div>';
			dropdown.style.display = 'block';
			return;
		}

		var html = '';
		for (var i = 0; i < results.length; i++) {
			var card = results[i];
			var url = cardBaseUrl + encodeURIComponent(card.slug) + '/';
			var highlighted = highlightMatch(card.label, term);

			html += '<div class="tcg-live-search-item" data-index="' + i + '">';
			html += '<a href="' + url + '">';
			if (card.thumb) {
				html += '<img src="' + card.thumb + '" alt="" class="tcg-live-search-thumb" loading="lazy">';
			}
			html += '<div class="tcg-live-search-info">';
			html += '<span class="tcg-live-search-title">' + highlighted + '</span>';
			if (card.set_rarity) {
				html += '<span class="tcg-live-search-meta">' + escHtml(card.set_rarity) + '</span>';
			}
			html += '</div>';
			html += '</a>';
			html += '</div>';
		}

		dropdown.innerHTML = html;
		dropdown.style.display = 'block';
		activeIndex = -1;
	}

	function highlightMatch(text, term) {
		var idx = text.toLowerCase().indexOf(term);
		if (idx === -1) return escHtml(text);
		return escHtml(text.substring(0, idx))
			+ '<mark>' + escHtml(text.substring(idx, idx + term.length)) + '</mark>'
			+ escHtml(text.substring(idx + term.length));
	}

	function updateActive(items) {
		for (var i = 0; i < items.length; i++) {
			items[i].classList.toggle('tcg-live-search-active', i === activeIndex);
		}
		if (items[activeIndex]) {
			items[activeIndex].scrollIntoView({ block: 'nearest' });
		}
	}

	function hideDropdown() {
		dropdown.style.display = 'none';
		dropdown.innerHTML = '';
		activeIndex = -1;
	}

	function escHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}
})();
