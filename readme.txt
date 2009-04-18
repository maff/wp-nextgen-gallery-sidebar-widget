=== NextGEN Gallery Sidebar Widget ===
Contributors:
Donate link: http://ailoo.net
Tags: image, picture, photo, widgets, gallery, images, nextgen-gallery
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 0.1

A widget to show NextGEN galleries in your sidebar.

== Description ==

The NextGEN widgets only allow showing of single images, I needed a solution to show links to galleries, so I wrote this widget. The widget lets you specify the following parameters

- Maximum Galleries: the number of galleries you want to show
- Gallery Order: you can select random, date added ascending or date added descending
- AutoThumb parameters: if you got [AutoThumb](http://wordpress.org/extend/plugins/autothumb/) installed, the widget will use its functions to resize the image to your needs. Use a string like `w=80&h=80&zc=1` here to show 80x80 square thumbnails.
- Output width/height: if you don't use AutoThumb, the plugin will set the HTML attributes width & height.
- Default Link Id: the widget assumes that you set up pages for each gallery and link the gallery to that page (you can use the NGG Gallery Editor to do this). If a gallery has no link set, it will use the default link (id of a page or post).

== Installation ==

1. Upload `ngg-sidebar-widget.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the widget in the widget editor.