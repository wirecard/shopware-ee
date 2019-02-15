# frozen_string_literal: true

module Const
  GITHUB_PHRASEAPP_PR_TITLE = '[PhraseApp] Update locales'
  GITHUB_PHRASEAPP_PR_BODY = 'Update locales from PhraseApp'
  GIT_PHRASEAPP_COMMIT_MSG = '[skip ci] Update translations from PhraseApp'
  GIT_PHRASEAPP_BRANCH_BASE = 'master'
  PHRASEAPP_PROJECT_ID = '9036e89959d471e0c2543431713b7ba1'
  PHRASEAPP_FALLBACK_LOCALE = 'en_US'

  # project-specific mappings for locales to filenames
  PHRASEAPP_TAG = 'shopware'
  LOCALE_SPECIFIC_MAP = {}.freeze
  LOCALE_LIST = ['en_US', 'de_DE'].freeze

  # paths relative to project root
  PLUGIN_I18N_DIR = File.join('Resources', 'languages').freeze

  # template settings
  TEMPLATE_SUFFIX = 'tpl'
  TEMPLATE_FOLDERS = [
    'Commands',
    'Components',
    'Controllers',
    'Exception',
    'Models',
    'Resources',
    'Subscriber',
  ].freeze
  TEMPLATE_KEY_PATTERN = /{{\s*strings\.(\w+)\s*}}/
end
