/**
 * Semantic-release config (cosmiconfig name: "release").
 *
 * cosmiconfig prefers `.releaserc.json` over `release.config.cjs` in the same directory,
 * so the full plugin list lives in `release.full.config.json` (not auto-discovered).
 *
 * - TIPSY_SEMANTIC_RELEASE_CI=1: file:// repo + analyzer only (CI dry-runs, no git push).
 * - Otherwise: full config (GitHub plugin, real remote).
 *
 * ParserOpts extend the angular preset with Conventional-Commits `!` breaking shorthand
 * (feat!:, fix(scope)!:). Same pattern as tishlang/tish.
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
