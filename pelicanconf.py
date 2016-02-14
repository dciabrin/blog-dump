#!/usr/bin/env python
# -*- coding: utf-8 -*- #
from __future__ import unicode_literals

AUTHOR = u'Damien Ciabrini'
AUTHORS = [u'Damien Ciabrini']
SITENAME = u"(blog-dump 'dciabrin)"
SITESUBTITLE = u"A coding and hacking diary"
SITEURL = 'http://damien.ciabrini.name'

PATH = 'content'

TIMEZONE = 'Europe/Paris'

DEFAULT_LANG = u'en'

# Feed generation is usually not desired when developing
FEED_DOMAIN = SITEURL
FEED_ALL_ATOM = None
CATEGORY_FEED_ATOM = None
TRANSLATION_FEED_ATOM = None
AUTHOR_FEED_ATOM = None
AUTHOR_FEED_RSS = None

DISPLAY_PAGES_ON_MENU = True
DISPLAY_CATEGORIES_ON_MENU = True

# TODO add cetegories Work, Hacks, Projects

# Blog customization
PLUGIN_PATHS = ['plugins']
PLUGINS = ['summary','tag_cloud', 'tipue_search']

# Blogroll
LINKS = (('Archives', '/archives.html'),)

# Social
SOCIAL = (('twitter', 'http://twitter.com/dciabrin'),
          ('github', 'http://github.com/dciabrin'),
          ('google-plus', 'https://plus.google.com/+DamienCiabrini'),
          ('linkedin', 'https://www.linkedin.com/in/damien-ciabrini-70514187'),
          )

# Static data
STATIC_PATHS = ['extra/CNAME']
EXTRA_PATH_METADATA = {'extra/CNAME': {'path': 'CNAME'},}

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

THEME = 'themes/blogdump' # 1
