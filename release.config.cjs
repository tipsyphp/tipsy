/**
 * Semantic-release config (cosmiconfig name: "release").
 *
 * Full plugin list lives in `release.full.config.json` (not auto-discovered as .releaserc).
 *
 * - TIPSY_SEMANTIC_RELEASE_CI=1: file:// repo + analyzer only (CI dry-runs, no git push).
 * - Otherwise: full config (GitHub plugin, real remote).
 *
 * Use preset "conventionalcommits" (NOT angular). Stock angular does not understand
 * `feat!:` / `fix!:` — those headers parse as no-type and produce no release. The
 * conventionalcommits preset supports the `!` breaking shorthand natively. The footer
 * form `BREAKING CHANGE: ...` also works on both presets and is the safest major trigger.
 */
const path = require("path");
const { execSync } = require("child_process");

const full = require(path.join(__dirname, "release.full.config.json"));

function readOnlyCi() {
  const root = execSync("git rev-parse --show-toplevel", { encoding: "utf8" }).trim();
  const fileUrl = "file://" + root.replace(/\\/g, "/") + "/.git";
  const pick = (name) => full.plugins.find((p) => Array.isArray(p) && p[0] === name);
  return {
    branches: full.branches,
    repositoryUrl: fileUrl,
    plugins: [
      pick("@semantic-release/commit-analyzer"),
      pick("@semantic-release/release-notes-generator"),
    ],
  };
}

module.exports =
  process.env.TIPSY_SEMANTIC_RELEASE_CI === "1" ? readOnlyCi() : full;
