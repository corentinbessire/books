#!/bin/bash

# Security Audit Script
# Checks composer and npm dependencies for known vulnerabilities
# Creates/updates GitHub issues for direct dependencies only

set -e

ISSUE_LABEL="security"
ISSUE_LABEL_COMPOSER="composer"
ISSUE_LABEL_NPM="npm"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Starting security audit..."

# Ensure required labels exist
ensure_labels() {
    echo "📋 Ensuring issue labels exist..."

    for label in "$ISSUE_LABEL" "$ISSUE_LABEL_COMPOSER" "$ISSUE_LABEL_NPM"; do
        if ! gh label list --json name -q ".[].name" | grep -q "^${label}$"; then
            case $label in
                "security")
                    gh label create "$label" --color "d73a4a" --description "Security vulnerability" 2>/dev/null || true
                    ;;
                "composer")
                    gh label create "$label" --color "6f42c1" --description "PHP/Composer dependency" 2>/dev/null || true
                    ;;
                "npm")
                    gh label create "$label" --color "f9a825" --description "Node/npm dependency" 2>/dev/null || true
                    ;;
            esac
        fi
    done
}

# Get direct Composer dependencies from composer.json
get_direct_composer_deps() {
    jq -r '(.require // {}) + (.["require-dev"] // {}) | keys[]' composer.json 2>/dev/null | grep -v "^php$" | grep -v "^ext-" || true
}

# Get direct npm dependencies from package.json
get_direct_npm_deps() {
    local package_json="${THEME_ROOT}/package.json"
    if [ -f "$package_json" ]; then
        jq -r '(.dependencies // {}) + (.devDependencies // {}) | keys[]' "$package_json" 2>/dev/null || true
    fi
}

# Check if a package is a direct dependency
is_direct_composer_dep() {
    local package="$1"
    get_direct_composer_deps | grep -q "^${package}$"
}

is_direct_npm_dep() {
    local package="$1"
    get_direct_npm_deps | grep -q "^${package}$"
}

# Find existing open issue for a dependency
find_existing_issue() {
    local dep_name="$1"
    local dep_type="$2"  # composer or npm

    # Search for open issues with matching title pattern
    gh issue list \
        --state open \
        --label "$ISSUE_LABEL" \
        --label "$dep_type" \
        --json number,title,body \
        --jq ".[] | select(.title | startswith(\"[Security] ${dep_name} \")) | {number, title, body}" \
        2>/dev/null || echo ""
}

# Create or update a GitHub issue for a vulnerability
create_or_update_issue() {
    local dep_name="$1"
    local dep_type="$2"
    local advisories="$3"
    local installed_version="$4"

    local title="[Security] ${dep_name} - Vulnerability detected"
    local body="## Security Vulnerability in \`${dep_name}\`

**Dependency Type:** ${dep_type}
**Installed Version:** ${installed_version}
**Detected:** $(date -u +"%Y-%m-%d %H:%M UTC")

### Advisories

${advisories}

---

### How to fix

"

    if [ "$dep_type" = "composer" ]; then
        body+="Run \`composer update ${dep_name}\` to update to a patched version.

Check available versions: \`composer show ${dep_name} --all\`"
    else
        body+="Run \`npm update ${dep_name}\` or \`npm install ${dep_name}@latest\` to update to a patched version.

Check available versions: \`npm view ${dep_name} versions\`"
    fi

    body+="

---
*This issue is automatically managed by the security audit workflow.*"

    # Check for existing issue
    local existing=$(find_existing_issue "$dep_name" "$dep_type")

    if [ -n "$existing" ]; then
        local issue_number=$(echo "$existing" | jq -r '.number')
        local existing_body=$(echo "$existing" | jq -r '.body')

        # Check if the content has changed (compare advisories section)
        if echo "$existing_body" | grep -q "$installed_version"; then
            echo -e "${YELLOW}  ↳ Issue #${issue_number} already exists and is up to date${NC}"
        else
            echo -e "${YELLOW}  ↳ Updating issue #${issue_number}${NC}"
            gh issue edit "$issue_number" --body "$body"
            gh issue comment "$issue_number" --body "🔄 **Updated:** Vulnerability information has been refreshed. Installed version: ${installed_version}"
        fi
    else
        echo -e "${RED}  ↳ Creating new issue${NC}"
        gh issue create \
            --title "$title" \
            --body "$body" \
            --label "$ISSUE_LABEL" \
            --label "$dep_type"
    fi
}

# Close issues for dependencies that are no longer vulnerable
close_resolved_issues() {
    local dep_type="$1"
    local vulnerable_deps="$2"

    echo "🧹 Checking for resolved vulnerabilities (${dep_type})..."

    # Get all open security issues for this dep type
    gh issue list \
        --state open \
        --label "$ISSUE_LABEL" \
        --label "$dep_type" \
        --json number,title \
        --jq '.[] | "\(.number)|\(.title)"' 2>/dev/null | while read -r issue_line; do

        local issue_number=$(echo "$issue_line" | cut -d'|' -f1)
        local issue_title=$(echo "$issue_line" | cut -d'|' -f2-)

        # Extract dependency name from title "[Security] dep-name - Vulnerability detected"
        local dep_name=$(echo "$issue_title" | sed -n 's/\[Security\] \(.*\) - Vulnerability detected/\1/p')

        if [ -n "$dep_name" ]; then
            # Check if this dependency is still in the vulnerable list
            if ! echo "$vulnerable_deps" | grep -q "^${dep_name}$"; then
                echo -e "${GREEN}  ↳ Closing issue #${issue_number} - ${dep_name} is no longer vulnerable${NC}"
                gh issue close "$issue_number" --comment "✅ **Resolved:** This vulnerability has been addressed. The dependency is no longer flagged by security audit."
            fi
        fi
    done
}

# Run Composer audit
run_composer_audit() {
    echo ""
    echo "📦 Running Composer security audit..."

    local audit_output
    local audit_exit_code=0

    audit_output=$(composer audit --format=json 2>/dev/null) || audit_exit_code=$?

    if [ $audit_exit_code -eq 0 ]; then
        echo -e "${GREEN}  ✓ No vulnerabilities found in Composer dependencies${NC}"
        close_resolved_issues "composer" ""
        return 0
    fi

    # Parse the audit output
    local vulnerable_direct_deps=""
    local advisories_json=$(echo "$audit_output" | jq -r '.advisories // {}')

    if [ "$advisories_json" = "{}" ] || [ -z "$advisories_json" ]; then
        echo -e "${GREEN}  ✓ No vulnerabilities found in Composer dependencies${NC}"
        close_resolved_issues "composer" ""
        return 0
    fi

    echo -e "${RED}  ⚠ Vulnerabilities found, checking direct dependencies...${NC}"

    # Iterate through each package with advisories
    echo "$advisories_json" | jq -r 'keys[]' | while read -r package; do
        if is_direct_composer_dep "$package"; then
            echo -e "${RED}  → ${package} (direct dependency)${NC}"
            vulnerable_direct_deps="${vulnerable_direct_deps}${package}\n"

            # Get installed version
            local installed_version=$(composer show "$package" --format=json 2>/dev/null | jq -r '.versions[0] // "unknown"')

            # Format advisories for this package
            local package_advisories=$(echo "$advisories_json" | jq -r --arg pkg "$package" '.[$pkg][] | "- **\(.title // .cve // "Unknown")**\n  - CVE: \(.cve // "N/A")\n  - Affected versions: \(.affectedVersions // "N/A")\n  - Link: \(.link // "N/A")\n"')

            create_or_update_issue "$package" "composer" "$package_advisories" "$installed_version"
        else
            echo -e "${YELLOW}  → ${package} (transitive dependency, skipping)${NC}"
        fi
    done

    # Close resolved issues
    close_resolved_issues "composer" "$(echo -e "$vulnerable_direct_deps")"
}

# Run npm audit
run_npm_audit() {
    echo ""
    echo "📦 Running npm security audit..."

    cd "$THEME_ROOT"

    local audit_output
    local audit_exit_code=0

    audit_output=$(npm audit --json 2>/dev/null) || audit_exit_code=$?

    # npm audit returns non-zero if vulnerabilities found
    local vuln_count=$(echo "$audit_output" | jq -r '.metadata.vulnerabilities.total // 0')

    if [ "$vuln_count" -eq 0 ]; then
        echo -e "${GREEN}  ✓ No vulnerabilities found in npm dependencies${NC}"
        cd - > /dev/null
        close_resolved_issues "npm" ""
        return 0
    fi

    echo -e "${RED}  ⚠ ${vuln_count} vulnerabilities found, checking direct dependencies...${NC}"

    local vulnerable_direct_deps=""

    # Get vulnerabilities grouped by package
    # npm audit --json structure varies by npm version, handle both formats
    local vulnerabilities=$(echo "$audit_output" | jq -r '
        if .vulnerabilities then
            .vulnerabilities | to_entries[] |
            select(.value.isDirect == true) |
            {
                name: .key,
                severity: .value.severity,
                via: .value.via,
                range: .value.range,
                fixAvailable: .value.fixAvailable
            }
        else
            .advisories // {} | to_entries[] |
            {
                name: .value.module_name,
                severity: .value.severity,
                title: .value.title,
                url: .value.url,
                range: .value.vulnerable_versions,
                recommendation: .value.recommendation
            }
        end
    ' 2>/dev/null)

    if [ -z "$vulnerabilities" ]; then
        # Fallback: check all reported packages against direct deps
        echo "$audit_output" | jq -r '
            if .vulnerabilities then .vulnerabilities | keys[]
            else .advisories | .[].module_name
            end
        ' 2>/dev/null | sort -u | while read -r package; do
            if is_direct_npm_dep "$package"; then
                echo -e "${RED}  → ${package} (direct dependency)${NC}"
                vulnerable_direct_deps="${vulnerable_direct_deps}${package}\n"

                # Get installed version
                local installed_version=$(npm list "$package" --depth=0 --json 2>/dev/null | jq -r ".dependencies[\"$package\"].version // \"unknown\"")

                # Get advisory info
                local package_advisories=$(echo "$audit_output" | jq -r --arg pkg "$package" '
                    if .vulnerabilities then
                        .vulnerabilities[$pkg] |
                        "- **Severity:** \(.severity // "unknown")\n- **Range:** \(.range // "N/A")\n- **Fix available:** \(.fixAvailable // "unknown")\n"
                    else
                        .advisories | to_entries[] | select(.value.module_name == $pkg) | .value |
                        "- **\(.title // "Unknown")**\n  - Severity: \(.severity // "N/A")\n  - Vulnerable versions: \(.vulnerable_versions // "N/A")\n  - Recommendation: \(.recommendation // "N/A")\n  - URL: \(.url // "N/A")\n"
                    end
                ' 2>/dev/null)

                create_or_update_issue "$package" "npm" "$package_advisories" "$installed_version"
            else
                echo -e "${YELLOW}  → ${package} (transitive dependency, skipping)${NC}"
            fi
        done
    else
        # Process direct vulnerabilities from npm audit
        echo "$vulnerabilities" | jq -r '.name' | sort -u | while read -r package; do
            if [ -n "$package" ] && [ "$package" != "null" ]; then
                echo -e "${RED}  → ${package} (direct dependency)${NC}"
                vulnerable_direct_deps="${vulnerable_direct_deps}${package}\n"

                local installed_version=$(npm list "$package" --depth=0 --json 2>/dev/null | jq -r ".dependencies[\"$package\"].version // \"unknown\"")

                local package_advisories=$(echo "$vulnerabilities" | jq -r --arg pkg "$package" '
                    select(.name == $pkg) |
                    "- **Severity:** \(.severity // "unknown")\n- **Range:** \(.range // "N/A")\n- **Fix available:** \(.fixAvailable // "unknown")\n"
                ')

                create_or_update_issue "$package" "npm" "$package_advisories" "$installed_version"
            fi
        done
    fi

    cd - > /dev/null

    # Close resolved issues
    close_resolved_issues "npm" "$(echo -e "$vulnerable_direct_deps")"
}

# Main execution
main() {
    ensure_labels
    run_composer_audit
    run_npm_audit

    echo ""
    echo "✅ Security audit complete"
}

main
