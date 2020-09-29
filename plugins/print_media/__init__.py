# -*- coding: utf-8 -*-
"""
print_media
===========

A Pelican plugin that transforms generated HTML to be more suitable
for printing. Based on pelican-cite.
"""
import logging
import os
import re
import sys
from typing import Dict, List, Match, Union

from jinja2 import Environment, FileSystemLoader
from pelican import Pelican, signals
from pelican.contents import Article, Page
from pelican.generators import ArticlesGenerator, PagesGenerator

logger = logging.getLogger(__name__)

A_RE = re.compile(r'<a href="[^"]+".*?</a>(?!<span class="refprint)')
AREF_RE = re.compile(r'<a href="([^"]+)"')


class ARefProcessor:
    def __init__(self, generators: List[Union[ArticlesGenerator, PagesGenerator]]):
        self.generators = generators

        logger.info("Generating references for print media")

    def process(self):
        # Process the articles and pages
        for generator in self.generators:
            if isinstance(generator, ArticlesGenerator):
                articles = (
                    generator.articles + generator.translations + generator.drafts
                )
                for article in articles:
                    self._process_article_content(article)
                    print(article)
            elif isinstance(generator, PagesGenerator):
                for page in generator.pages:
                    self._process_article_content(page)


    def _process_article_content(self, article: Union[Article, Page]):
        content = article._content
        refs = A_RE.findall(content)
        try:
            ref_dict = article.print_references
        except:
            ref_dict = {}
        counter = 1
        
        def extend_aref(match: Match) -> str:
            nonlocal counter
            full_ref = match.group(0)
            if "refprint" in full_ref:
                return full_ref
            if "<img" in full_ref:
                return full_ref

            url = AREF_RE.search(full_ref).group(1)
            url_id = ref_dict.get(url, False)
            if not url_id:
                url_id = counter
                ref_dict[url] = counter
                counter += 1
            
            new_ref = '<span class="refprint d-none d-print-inline"><a href="#prt_ref_%s" ref_id="%s"></a></span>'%\
                (url_id, url_id)
            extended = full_ref+new_ref
            return extended

        # print(dir(article))
        article.print_references = ref_dict
        article._content = A_RE.sub(extend_aref, content)


def extend_aref(generators):
    processor = ARefProcessor(generators)
    processor.process()


def register():
    signals.all_generators_finalized.connect(extend_aref)
