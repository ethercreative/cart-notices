/* global Craft, $ */

Craft.CartNotices = Craft.BaseElementIndex.extend({

	noticeTypes: null,
	$newNoticeBtnType: null,
	$newNoticeBtn: null,

	init: function (elementType, $container, settings) {
		this.on('selectSource', $.proxy(this, 'updateButton'));
		this.on('selectSite', $.proxy(this, 'updateButton'));
		this.base(elementType, $container, settings);
	},

	afterInit: function () {
		this.noticeTypes = window.noticeTypes;
		this.base();
	},

	getDefaultSourceKey: function () {
		if (
			this.settings.context === 'index'
			&& typeof window.defaultType !== 'undefined'
		) {
			for (let i = 0, l = this.$sources.length; i < l; ++i) {
				const $source = $(this.$sources[i]);

				if ($source.data('handle') === window.defaultType)
					return $source.data('key');
			}
		}

		return this.base();
	},

	updateButton: function () {
		if (!this.$source)
			return;

		const selectedSourceHandle = this.$source.data("key")
			, isIndex              = this.settings.context === "index";

		if (isIndex && typeof history !== "undefined") {
			let uri = "cart-notices";

			if (selectedSourceHandle)
				uri += "/" + selectedSourceHandle;

			history.replaceState({}, "", Craft.getUrl(uri));
		}

		if (this.noticeTypes.length === 0)
			return;

		if (this.$newNoticeBtnType)
			this.$newNoticeBtnType.remove();

		let selectedType;

		if (selectedSourceHandle) {
			let i = this.noticeTypes.length;
			while (i--) {
				if (this.noticeTypes[i].handle === selectedSourceHandle) {
					selectedType = this.noticeTypes[i];
					break;
				}
			}
		}

		this.$newNoticeBtnType = $('<div class="btngroup submit" />');

		let $menuBtn, href, label;

		if (selectedType) {
			href  = this._getTypeTriggerHref(selectedType);
			label =
				isIndex
					? Craft.t('cart-notices', "New Notice")
					: Craft.t('cart-notices', "New {type} notice", { type: selectedType.name });

			this.$newNoticeBtn = $(
				'<a class="btn submit add icon" ' + href + '>'
					+ Craft.escapeHtml(label) +
				'</a>'
			).appendTo(this.$newNoticeBtnType);

			if (!isIndex) {
				this.addListener(this.$newNoticeBtn, "click", function (e) {
					this._openCreateNoticeModal(
						e.currentTarget.getAttribute("data-id")
					);
				});
			}

			if (this.noticeTypes.length > 1) {
				$menuBtn = $('<div class="btn submit menubtn" />').appendTo(
					this.$newNoticeBtnType
				);
			}
		} else {
			this.$newNoticeBtn = $menuBtn = $(
				'<div class="btn submit add icon menubtn">'
					+ Craft.t('cart-notices', "New Notice") +
				'</div>'
			).appendTo(this.$newNoticeBtnType);
		}

		if ($menuBtn) {
			let menuHtml = '<div class="menu"><ul>';

			for (let i = 0, l = this.noticeTypes.length; i < l; ++i) {
				let type = this.noticeTypes[i];

				if (isIndex || type.id !== selectedType.id) {
					href  = this._getTypeTriggerHref(type);
					label =
						isIndex
							? type.name
							: Craft.t('cart-notices', "New {type} notice", { type: type.name });
					menuHtml +=
						'<li><a ' + href + '>'
						+ Craft.escapeHtml(label) +
						'</a></li>';
				}
			}

			menuHtml += '</ul></div>';

			$(menuHtml).appendTo(this.$newNoticeBtnType);
			const menuBtn = new Garnish.MenuBtn($menuBtn);

			if (!isIndex) {
				menuBtn.on("optionSelect", $.proxy(function (e) {
					this._openCreateNoticeModal(e.option.getAttribute("data-id"));
				}, this));
			}
		}

		this.addButton(this.$newNoticeBtnType);
	},

	_getTypeTriggerHref: function (type) {
		if (this.settings.context !== "index")
			return 'data-id="' + type.id + '"';

		let uri = 'cart-notices/new?type=' + type.handle;

		if (this.siteId && this.siteId !== Craft.primarySiteId)
			for (let i = 0, l = Craft.sites.length; i < l; ++i)
				if (Craft.sites[i].id === this.siteId)
					uri += '/' + Craft.sites[i].handle;

		return 'href="' + Craft.getUrl(uri) + '"';
	},

	_openCreateNoticeModal: function (typeId) {
		if (this.$newNoticeBtn.hasClass("loading"))
			return;

		let type;
		for (let i = 0, l = this.noticeTypes.length; i < l; ++i) {
			if (this.noticeTypes[i].id === typeId) {
				type = this.noticeTypes[i];
				break;
			}
		}

		if (!type)
			return;

		this.$newNoticeBtn.addClass("inactive");
		let newNoticeBtnText = this.$newNoticeBtn.text();
		this.$newNoticeBtn.text(
			Craft.t('cart-notices', "New {type} notice", { type: type.name })
		);

		Craft.createElementEditor(this.elementType, {
			hudTrigger: this.$newNoticeBtnType,
			elementType: "craft\\elements\\Notice",
			siteId: this.siteId,
			attributes: {
				typeId: typeId,
			},
			onBeginLoading: $.proxy(function () {
				this.$newNoticeBtn.addClass("loading");
			}, this),
			onEndLoading: $.proxy(function () {
				this.$newNoticeBtn.removeClass("loading");
			}, this),
			onHideHud: $.proxy(function () {
				this.$newNoticeBtn.removeClass("inactive").text(newNoticeBtnText);
			}, this),
			onSaveElement: $.proxy(function (response) {
				let typeSourceKey = "type:" + typeId;

				if (this.sourceKey !== typeSourceKey)
					this.selectSourceByKey(typeSourceKey);

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this),
		});
	},

});

Craft.registerElementIndexClass(
	'ether\\cartnotices\\elements\\Notice',
	Craft.CartNotices
);