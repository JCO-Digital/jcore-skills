#!/usr/bin/env bash
# Scan theme/plugin directories for Timber 1.x API usage that must change for Timber 2.
#
# Usage: scan-timber1.sh <dir> [<dir>...]
# Exit status: 0 = nothing found, 1 = findings, 2 = usage error.
#
# grep-based: every hit is a lead to read, not a verdict. vendor/, node_modules/
# and build/ are skipped.

set -uo pipefail

if [[ $# -eq 0 ]]; then
	echo "usage: $(basename "$0") <dir> [<dir>...]" >&2
	exit 2
fi

for d in "$@"; do
	[[ -d "$d" ]] || { echo "not a directory: $d" >&2; exit 2; }
done

found=0

# check <php|twig|both> <title> <fix> <extended regex> [<exclude regex>]
check() {
	local kind=$1 title=$2 fix=$3 re=$4 exclude=${5:-}
	local includes=()
	case $kind in
		php) includes=(--include='*.php') ;;
		twig) includes=(--include='*.twig') ;;
		both) includes=(--include='*.php' --include='*.twig') ;;
	esac
	local out
	out=$(grep -rnE "${includes[@]}" \
		--exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=build --exclude-dir=.git \
		-e "$re" "${DIRS[@]}" 2>/dev/null)
	if [[ -n "$exclude" && -n "$out" ]]; then
		out=$(printf '%s\n' "$out" | grep -vE -e "$exclude")
	fi
	if [[ -n "$out" ]]; then
		found=1
		printf '\n\033[1m## %s\033[0m\n   fix: %s\n' "$title" "$fix"
		printf '%s\n' "$out" | sed 's/^/   /'
	fi
}

DIRS=("$@")

# --- PHP: bootstrapping and object creation ---------------------------------
check php "Timber constructor (fatal in 2.x)" \
	"\\Timber\\Timber::init()" \
	'new[[:space:]]+\\?Timber\\Timber[[:space:]]*(\(|;)'
check php "Direct instantiation of Timber objects" \
	"Timber::get_post() / get_posts() / get_term() / get_user() / get_comment() / get_menu() / get_image()" \
	'new[[:space:]]+\\?(Timber\\)?(Post|PostQuery|Term|User|Comment|Menu|Image|Attachment)[[:space:]]*\('
check php "Timber 1 class aliases (removed)" \
	"namespaced Timber\\<Class>, or the Timber:: factory / get_*() Twig function" \
	'(^|[^A-Za-z_\\])Timber(Post|Image|Term|User|Menu|MenuItem|Comment|Site|Theme|Helper|ImageHelper|URLHelper|Loader|Core|Archives|Twig|PostGetter|TermGetter|QueryIterator|Request)([^A-Za-z_]|$)'
check php "Deprecated/removed Timber static methods" \
	"get_context→context, fetch→compile, query_post→get_post, get_pagination→posts.pagination, add_route→upstatement/routes" \
	'Timber::(get_context|fetch|query_post|query_posts|get_pagination|add_route)[[:space:]]*\('
check php "Timber static properties replaced by filters" \
	"timber/twig/environment/options filter (autoescape, cache)" \
	'Timber::\$(autoescape|cache|twig_cache)'
check php "Query string passed to get_posts (removed)" \
	"pass an array: Timber::get_posts( array( 'post_type' => … ) )" \
	"get_posts[[:space:]]*\([[:space:]]*['\"][a-z_]+="
check php "Post class passed as argument (removed)" \
	"register the class in the timber/post/classmap filter" \
	"Timber::get_posts?[[:space:]]*\([^)]*,[[:space:]]*['\"][A-Z\\\\]"
check php "Old get_term/get_terms extra arguments" \
	"Timber::get_term( \$id ) / Timber::get_terms( array( 'taxonomy' => … ) + \$args ); remember hide_empty now defaults to true" \
	"Timber::get_terms?[[:space:]]*\([^)]*,"
check php "->terms() with a taxonomy string" \
	"->terms( array( 'taxonomy' => 'x' ) )" \
	"->terms[[:space:]]*\([[:space:]]*['\"]"

# --- PHP: Twig classes and hooks --------------------------------------------
check php "Twig 1 / Timber Twig wrapper classes" \
	"\\Twig\\TwigFunction, \\Twig\\TwigFilter, \\Twig\\TwigTest, \\Twig\\Environment" \
	'Twig_(Simple)?(Function|Filter|Test)|Twig_Environment|Timber\\Twig_(Function|Filter)'
check php "Renamed Timber hooks" \
	"timber/context, timber/twig, timber/render/*, timber/compile/*, timber/post/meta, timber/locations … (see references/timber2-api.md → Hooks)" \
	"['\"](timber_context|get_twig|timber_render_(file|data)|timber_compile_(file|data|done)|timber_(post|term|user|comment)_get_meta(_field)?(_pre)?|timber_site_set_meta|timber/loader/paths|timber/cache/location|timber/term/meta/field|timber/URLHelper/[a-z_/]+)['\"]"
check php "Removed Timber hooks / class map" \
	"timber/post/classmap; Post::setup()/teardown() instead of posts_iterator" \
	"Timber\\\\PostClassMap|timber/class/posts_iterator|timber/get_posts/mirror_wp_get_posts|timber_post_getter_get_posts"
check php "timber/twig/functions|filters (now arrays, not the environment)" \
	"review: only add/remove array entries here; do \$twig->addFunction() in timber/twig" \
	"['\"]timber/twig/(functions|filters|escapers)['\"]"
check php "Removed/deprecated PHP methods on Timber objects" \
	"see references/timber2-api.md → Removed/renamed object methods (review: may be a non-Timber object)" \
	'->(get_preview|get_thumbnail|get_permalink|get_post_type|get_terms|get_title|get_content|get_author|get_categories|get_tags|get_parent|get_edit_url|get_comment_count|import_field|get_meta_field|get_post_custom|get_pathinfo|get_src)[[:space:]]*\(' \
	'\$(_?product|order|gateway|cart|item|customer|coupon|wc_[a-z_]*)->get_'

# --- Twig --------------------------------------------------------------------
check twig "Deprecated Twig object functions" \
	"get_image() / get_post() / get_posts() / get_term() / get_user() (Image() also accepted URLs: use get_external_image())" \
	'(^|[^A-Za-z_.])(Timber)?(Image|Post|Term|User)[[:space:]]*\('
check twig "Removed getter methods (render empty, no error)" \
	"drop the get_: thumbnail, link, title, content, children, terms, path, parent, author, next, prev …; preview→excerpt" \
	'\.(get_thumbnail|get_link|get_permalink|get_title|get_content|get_children|get_terms|get_path|get_parent|get_author|get_categories|get_category|get_tags|get_comments|get_comment_count|get_next|get_prev|get_modified_date|get_modified_time|get_edit_url|get_src|get_url|get_image|get_field|get_preview|get_post_type|get_format|get_pagination)([^A-Za-z_]|$)' \
	'(product|_product|order|gateway|method|rate|cart|cart_item|coupon|customer|checkout|shipping_method|package)\.get_'
check twig "post.preview (deprecated)" \
	"post.excerpt (the jcore |preview filter is fine)" \
	'(post|item|p)\.preview([^A-Za-z_]|$)'
check twig "post.custom (returns false in 2.x)" \
	"post.meta('field')" \
	'\.custom(\.|\[)'
check twig "Removed request object" \
	"read \$_GET in PHP, sanitize, put it in the context" \
	'request\.(get|post)'
check twig "Old Twig tags" \
	"{% apply x %}…{% endapply %} / {% apply spaceless %}" \
	'\{%-?[[:space:]]*(end)?(filter|spaceless)([[:space:]]|-?%\})'
check twig "Removed/deprecated Twig filters" \
	"|get_type removed; |get_class, |print_r deprecated (debug leftovers?)" \
	'\|[[:space:]]*(get_type|get_class|print_r)'
check twig "wp_head / wp_footer from context (removed)" \
	"{{ function('wp_head') }} / {{ function('wp_footer') }}" \
	'\{\{-?[[:space:]]*wp_(head|footer)[[:space:]]*-?\}\}'
check twig "Removed site getters" \
	"site.link" \
	'site\.(get_link|get_url)'
check twig "jcore timber_set_product moved into jcore namespace" \
	"fn('jcore\\\\timber_set_product', post)" \
	"(fn|function)\\([[:space:]]*['\"]timber_set_product"

if [[ $found -eq 0 ]]; then
	echo "No Timber 1 patterns found in: ${DIRS[*]}"
	echo "Still check by hand: custom-field property access, ACF return formats with get_image(), empty ID lists passed to get_posts(), hide_empty on get_terms()."
	exit 0
fi

printf '\nStill check by hand: custom-field property access, ACF return formats with get_image(), empty ID lists passed to get_posts(), hide_empty on get_terms().\n'
exit 1
