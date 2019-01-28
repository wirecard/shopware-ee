require 'logger'
require 'rainbow/refinement'
require_relative 'const.rb'
require_relative 'env.rb'
require_relative 'wd-git.rb'
require_relative 'wd-github.rb'

using Rainbow

# Project-specific helpers
class WdProject
  attr_reader :translations_path
  attr_reader :translations_new_path

  def initialize
    @log = Logger.new(STDOUT, level: Env::DEBUG ? 'DEBUG' : 'INFO')
    @repo = Env::TRAVIS_REPO_SLUG
    @head = Env::TRAVIS_BRANCH

    @phraseapp_fallback_locale = Const::PHRASEAPP_FALLBACK_LOCALE
    @locale_specific_map = Const::LOCALE_SPECIFIC_MAP
    @translations_path = File.join(Const::PLUGIN_I18N_DIR, "#{@locale_specific_map[@phraseapp_fallback_locale.to_sym] ||
      @phraseapp_fallback_locale}.json")
    @translations_new_path = @translations_path + '.new'
    @template_suffix = Const::TEMPLATE_SUFFIX
    @template_folders = Const::TEMPLATE_FOLDERS
    @template_key_pattern = Const::TEMPLATE_KEY_PATTERN
  end

  # Returns true if source code has modified keys compared to the downloaded locale file of the fallback locale id
  def worktree_has_key_changes?
    json_generate && has_key_changes?
  end

  # Compares the keys from source and PhraseApp and returns true if they have any difference in keys, false otherwise.
  def has_key_changes?
    translations_object = JSON.parse(File.read(@translations_path, :encoding => 'utf-8'))
    translations_new_object = JSON.parse(File.read(@translations_new_path, :encoding => 'utf-8'))

    existing_keys = translations_object.map { |key, value| key }
    new_keys = translations_new_object.map { |key, value| key }

    @log.info("Number of keys in the existing translations file: #{existing_keys.length}")
    @log.info("Number of keys in the new translations file: #{new_keys.length}")

    @log.info("Removed keys: #{existing_keys - new_keys}")
    @log.info("Added keys: #{new_keys - existing_keys}")

    # keys are unique; we use the intersection to detect differences
    has_key_changes = (new_keys.length != existing_keys.length) || (new_keys & existing_keys != new_keys)

    if has_key_changes
      @log.warn('Changes to translatable keys have been detected in the working tree.'.yellow.bright)
    else
      @log.info('No changes to translatable keys have been detected in the working tree.'.green.bright)
    end

    has_key_changes
  end

  # Parses all template files for keys and returns them in an array.
  def get_keys
    keys = []
    template_file_patterns = @template_folders.map { |folder| "#{folder}/**/*.*.#{@template_suffix}" }
    template_file_paths = Dir.glob(template_file_patterns)

    template_file_paths.each do |file_path|
      file_contents = File.read(file_path, :encoding => 'utf-8')
      keys += file_contents.scan(@template_key_pattern).map { |key| key[0] }
    end

    keys.uniq
  end

  # Generates a new translations file from the current keys.
  def json_generate
    @log.info('Generating new translations file...')

    translations_object = JSON.parse(File.read(@translations_path, :encoding => 'utf-8'))
    translations_new_object = {}

    get_keys.each do |key|
      translations_new_object[key] = translations_object[key] || ''
    end

    File.write(@translations_new_path, JSON.pretty_generate(translations_new_object))
  end

  # Adds, commits, pushes to remote any modified/untracked files in the i18n dir. Then creates a PR.
  def commit_push_pr_locales()
    path = Const::PLUGIN_I18N_DIR
    base = Const::GIT_PHRASEAPP_BRANCH_BASE
    commit_msg = Const::GIT_PHRASEAPP_COMMIT_MSG
    pr_title = Const::GITHUB_PHRASEAPP_PR_TITLE
    pr_body = Const::GITHUB_PHRASEAPP_PR_BODY

    WdGit.new.commit_push(@repo, @head, path, commit_msg)
    WdGithub.new.create_pr(@repo, base, @head, pr_title, pr_body)
  end
end
