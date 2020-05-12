(function () {
	'use strict';

	let usersList = document.getElementById('calendarp-users');
	let allowUserButton = document.getElementById('allow-user-add');
	let allowUserSelector = document.getElementById('allow-user');

	/**
	 * Make an AJAX call
	 */
	function call(action, data) {
		data.action = action;
		data.nonce = calendarpSettings.nonce;
		return jQuery.post(ajaxurl, data);
	}

	/**
	 * Remove a user row
	 */
	function removeUserRowElement(id) {
		if (confirm('Are you sure?')) {
			let element = document.getElementById('calendarp-' + id);
			if (element) {
				element.parentNode.removeChild(element);
				call('calendarp_remove_user', {id: id});
			}
		}
	}

	/**
	 * Create a new user row
	 */
	function addUserRowElement(user) {
		let listNode, linkNode, removeNode, spanNode;

		// Create the list item
		listNode = document.createElement('li');
		listNode.id = 'calendarp-' + user.id;

		if (user.link) {
			// User has edit link
			linkNode = document.createElement('a');
			linkNode.href = user.link;
			linkNode.innerHTML = user.name;
			linkNode.title = user.linkTitle;
			listNode.appendChild(linkNode);
		}
		else {
			spanNode = document.createElement('span');
			spanNode.innerHTML = user.name;
			listNode.appendChild(spanNode);
		}

		if (user.removable) {
			// The remove link. Only for users that are not the current one
			removeNode = document.createElement('a');
			removeNode.setAttribute('data-id', user.id);
			removeNode.href = '#';
			removeNode.className = 'dashicons-no-alt dashicons';
			removeNode.title = calendarpSettings.removeTitle;
			removeNode.addEventListener('click', function (e) {
				// Remove the list item on click
				e.preventDefault();
				removeUserRowElement(this.getAttribute('data-id'));
			});
			listNode.appendChild(removeNode);
		}


		usersList.appendChild(listNode);
	}

	calendarpSettings.usersList.forEach(addUserRowElement);

	if (allowUserSelector) {
		// Toggle allow button when the select changes
		allowUserSelector.addEventListener('change', function () {
			let className = 'hidden';
			if (this.value) {
				if (allowUserButton.classList)
					allowUserButton.classList.remove(className);
				else
					allowUserButton.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
			}
			else {
				if (allowUserButton.classList) {
					allowUserButton.classList.add(className);
				}
				else {
					allowUserButton.className += ' ' + className;
				}
			}
		});

		// Add new user when clicking on Allow button
		allowUserButton.addEventListener('click', function (e) {
			e.preventDefault();
			this.setAttribute('disabled', 'disabled');
			call('calendarp_add_new_allowed_user', {id: allowUserSelector.value})
				.always(function (response) {
					if (response.success) {
						addUserRowElement(response.data);
					}
					allowUserButton.removeAttribute('disabled');
				});
		});
	}

}());
