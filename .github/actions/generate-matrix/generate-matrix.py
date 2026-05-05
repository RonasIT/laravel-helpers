#!/usr/bin/env python3
"""Generate CI matrix: minimum versions from composer.json and latest versions from public APIs."""

import json
import os
import re
import urllib.request


def parse_composer_json():
    """Extract minimal PHP and Laravel versions from composer.json requirements."""
    workspace = os.getenv("GITHUB_WORKSPACE", ".")
    composer_path = os.path.join(workspace, "composer.json")

    with open(composer_path, "r", encoding="utf-8") as file:
        composer = json.load(file)

    php_constraint = composer.get("require", {}).get("php", ">=8.0")
    laravel_constraint = composer.get("require", {}).get("laravel/framework", ">=11.0")

    php_min = extract_min_version(php_constraint, default="8.0")
    laravel_min = extract_min_version(laravel_constraint, default="11.0")

    return php_min, laravel_min


def extract_min_version(requirement, default):
    """Extract minimal major.minor from constraint like >=8.3 or >=8.3.0."""
    match = re.search(r">=\s*(\d+\.\d+)", requirement)
    return match.group(1) if match else default


def fetch_php_versions(php_min):
    """Fetch all supported PHP major.minor versions >= php_min from php.net."""
    try:
        with urllib.request.urlopen("https://www.php.net/releases/?json", timeout=15) as response:
            payload = json.load(response)

        supported = payload["8"]["supported_versions"]
        return [v for v in supported if v >= php_min]
    except Exception:
        return [php_min]


def fetch_laravel_versions(laravel_min):
    """Fetch all stable Laravel major series from Packagist >= laravel_min major."""
    min_major = int(laravel_min.split(".")[0])

    try:
        with urllib.request.urlopen("https://repo.packagist.org/p2/laravel/framework.json", timeout=20) as response:
            payload = json.load(response)

        version = payload["packages"]["laravel/framework"][0]["version"]
        match = re.search(r"v?(\d+)\.", version)
        if not match:
            return [f"{min_major}.*"]

        latest_major = int(match.group(1))
        return [f"{major}.*" for major in range(min_major, latest_major + 1)]
    except Exception:
        return [f"{min_major}.*"]


def generate_matrix(php_versions, laravel_versions):
    """Generate cross-product matrix and exclude latest+latest for non-coverage job."""
    return {
        "php-version": php_versions,
        "laravel-version": laravel_versions,
        "exclude": [
            {
                "php-version": php_versions[-1],
                "laravel-version": laravel_versions[-1],
            }
        ],
    }


def write_github_outputs(result):
    """Write GitHub Actions step outputs when GITHUB_OUTPUT is available."""
    github_output = os.getenv("GITHUB_OUTPUT")
    if not github_output:
        return

    with open(github_output, "a", encoding="utf-8") as file:
        file.write(f"matrix={json.dumps(result['matrix'])}\n")
        file.write(f"php-latest={result['php-latest']}\n")
        file.write(f"laravel-latest={result['laravel-latest']}\n")


if __name__ == "__main__":
    php_min, laravel_min = parse_composer_json()

    php_versions = fetch_php_versions(php_min)
    laravel_versions = fetch_laravel_versions(laravel_min)

    matrix = generate_matrix(php_versions, laravel_versions)
    result = {
        "matrix": matrix,
        "php-latest": php_versions[-1],
        "laravel-latest": laravel_versions[-1],
    }

    write_github_outputs(result)
    print(json.dumps(result))
