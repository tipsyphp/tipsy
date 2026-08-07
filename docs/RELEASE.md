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

Use [conventional commits](https://www.conventionalcommits.org/). Tipsy CI uses the
**`conventionalcommits` preset** (not angular), so both of these cut a **major**:

```
feat!: Tipsy 2.0 PHP 8.3 hard break

# OR (always works, even on stock angular — prefer this if you want belt-and-suspenders):
feat: Tipsy 2.0 PHP 8.3 hard break

BREAKING CHANGE: requires PHP 8.3+; default DB is PostgreSQL; typed public API.
```

Other bumps:

```
feat: add something new        → minor
fix: fix a bug                 → patch
perf: make it faster           → patch
```

`docs:` and `chore:` do **not** trigger a release.

**Do not rely on PR titles alone** — semantic-release reads **git commit messages on
`master`/`main`**. If you squash-merge, put `feat!:` / `BREAKING CHANGE:` in the
**squash commit subject/body**.

### Cutting `v2.0.0` specifically (from `0.11.x`)

A major bump from `0.11.12` becomes **`1.0.0`**, not `2.0.0` (semver/semrel rule for
0.x). To ship Tipsy 2.0 as `v2.0.0`:

1. Let CI open the prerelease (likely `v1.0.0`), **or**
2. Before promoting: edit the GitHub release/tag to **`v2.0.0`** (and the
   `release/v…` branch name if you care), **or**
3. Create an annotated `v1.0.0` tag on the pre-2.0 tip first, then push a `feat!:`
   commit so the next major is `2.0.0`.

Then uncheck “pre-release” to publish to Packagist.

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
