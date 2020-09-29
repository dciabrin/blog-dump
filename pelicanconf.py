#!/usr/bin/env python
# -*- coding: utf-8 -*- #
from __future__ import unicode_literals

AUTHOR = u'Damien Ciabrini'
AUTHORS = [u'Damien Ciabrini']
SITENAME = u"(blog-dump 'dciabrin)"
SITESUBTITLE = u"A coding and hacking diary"
SITEURL = 'https://dciabrin.net'

PATH = 'content'

TIMEZONE = 'Europe/Paris'

DEFAULT_LANG = u'en'

# Feed generation is usually not desired when developing
FEED_DOMAIN = SITEURL
# FEED_ALL_ATOM = 'feeds/all.atom.xml'
# TAG_FEED_ATOM = 'feeds/%s.tag.atom.xml'
FEED_ALL_ATOM = None
CATEGORY_FEED_ATOM = None
TRANSLATION_FEED_ATOM = None
AUTHOR_FEED_ATOM = None
AUTHOR_FEED_RSS = None

DISPLAY_PAGES_ON_MENU = True
DISPLAY_CATEGORIES_ON_MENU = True

# TODO add cetegories Work, Hacks, Projects

# Blog customization
PLUGIN_PATHS = ['localplugins','plugins']
PLUGINS = [
    'assets',
    'neighbors',          # navigation at the end of the page
    'render_math',        # inline math in article
    'share_post',         # static share buttons (reddit, hn, twitter...) 
    'print_media',        # nice rendering of aref for print media
    'tipue_search',       # static search index
]

# TODO check whether we need this
ASSET_CONFIG = (
    ('less_bin', 'lessc'),
)

LIBSASS_STYLE = 'expanded'

# Blogroll
LINKS = (('Archives', '/archives.html'),)

# Social
SOCIAL = (('twitter', 'http://twitter.com/dciabrin'),
          ('github', 'http://github.com/dciabrin'),
          ('linkedin', 'https://www.linkedin.com/in/damien-ciabrini-70514187'),
          )

# Static data
STATIC_PATHS = [
    'img/covers',
    'img/',
    'img/favicon.ico',
    'extra/CNAME',
    ]
EXTRA_PATH_METADATA = {
    'extra/CNAME': {'path': 'CNAME'},
    'img/favicon.ico': {'path': 'favicon.ico'},
}

# do not copy theme's sources files in the generated website 
STATIC_EXCLUDES = ['vendor']

# Do not parse content that ships with articles
READERS = {
    'png': None
}

# Generate those pages
DIRECT_TEMPLATES = (('index', 'tags', 'categories', 'archives', 'search'))

# Structure and permalinks
ARTICLE_URL = 'posts/{date:%Y}/{date:%m}/{slug}.html'
ARTICLE_SAVE_AS = 'posts/{date:%Y}/{date:%m}/{slug}.html'

# I'm the only author, thanks
AUTHOR_SAVE_AS = ''

DEFAULT_PAGINATION = 5

# License
LICENSE = 'CC-BY 4.0'
LICENSE_URL = 'CC-BY 4.0'

# Uncomment following line if you want document-relative URLs when developing
RELATIVE_URLS = True

# Custom theme
THEME = 'mytheme'
COLOR_SCHEME_CSS = 'friendly.css'

# for parallax
import os
import sys
import imagesize
import subprocess
def img_ratio(url):
    width, height = imagesize.get(os.path.join('content',url))
    return "%.3f"%(width/height)
def base_color(url):
    gfx=os.path.join('content',url)
    color=subprocess.getoutput("convert '%s' -scale 1x1\! -format '%%[hex:u]' info:-"%gfx)
    return "#"+color

JINJA_FILTERS = {
    'parallax': img_ratio,
    'base_color': base_color
}

# CommonHTML is the only backend that renders properly
# when mathml markups are tweaked with CSS
MATH_JAX = {
    'source': "'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/latest.js?config=TeX-AMS_CHTML'",
    'linebreak_automatic': True
}

FAVICON= 'favicon.ico'

DISABLE_CUSTOM_THEME_JAVASCRIPT = 1
FOOTER_ARTICLE_NAVIGATION = 1

# custom backgrounds
HEADER_COVER = 'img/covers/big-dodzy-59J9tB7KoOU-unsplash.jpg'
ARTICLE_COVER = 'img/covers/alex-knight-Ys-DBJeX0nE-unsplash.jpg'
SEARCH_COVER = 'img/covers/andrew-neel-1-29wyvvLJA-unsplash.jpg'
# NO_HEADER_COVER = 1
