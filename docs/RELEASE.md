# How to Release Tipsy

Step-by-step guide. Do these in order.

The pipeline mirrors [tish](https://github.com/tishlang/tish): CI creates a **prerelease**; you promote it to a full release to publish to Packagist.

---

## Before You Start: One-Time Setup

### 1. GitHub Secrets (Settings → Secrets and variables → Actions)

| Secret | How to get it |
|--------|---------------|
| `PACKAGIST_USERNAME` | Your Packagist username |
| `PACKAGIST_TOKEN` | [packagist.org/profile](https://packagist.org/profile/) → Show API Token |
| `CODECOV_TOKEN` | Optional — Codecov project token |

Also ensure the Packagist package `tipsyphp/tipsy` has GitHub as its repository (Auto-Update / GitHub Service Hook).

---

## Every Release

### Step 1: Commit with a release-triggering message

Use [conventional commits](https://www.conventionalcommits.org/):

```
feat: add something new        → minor (1.0.0 → 1.1.0)
fix: fix a bug                 → patch (1.0.0 → 1.0.1)
perf: make it faster           → patch
feat!: breaking change         → major (1.0.0 → 2.0.0)
```

`docs:` and `chore:` do **not** trigger a release.

For the Tipsy **2.0** hard break, use a breaking commit (e.g. `feat!: PHP 8.3 modernization`) so semantic-release bumps the major version. If you need an exact tag like `v2.0.0` and the dry-run suggests something else, edit the prerelease tag/name in the GitHub UI before promoting (or create the prerelease manually).

### Step 2: Push to `master` (or `main`)

```bash
git push origin master
```

Pushes to `v2` / feature branches run tests only — they do **not** create prereleases.

### Step 3: Let CI run

- Open **Actions** → **CI (test, coverage, release)**
- Wait for **test** (PHP 8.3/8.4 × pgsql/mysql) and **Release (prerelease branch + GitHub API)**

If nothing releaseable:

- Commits lacked `feat` / `fix` / `perf` / `BREAKING CHANGE` → add one and push again
- Build/test failures → fix and push again

### Step 4: Promote the prerelease to a full release

1. Go to **Releases**
2. Open the latest **prerelease** (e.g. `v2.0.0`)
3. **Edit** → uncheck **Set as a pre-release** → **Update release**

This runs **Packagist release**, which pings Packagist to index the new tag.

---

## Verify

```bash
composer show tipsyphp/tipsy
# or
curl -sS https://repo.packagist.org/p2/tipsyphp/tipsy.json | jq '.packages["tipsyphp/tipsy"][0].version'
```

Install:

```bash
composer require tipsyphp/tipsy:^2.0
```

---

## Manual re-publish

**Actions** → **Packagist release** → **Run workflow** → tag `vX.Y.Z`.
