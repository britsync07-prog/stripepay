---
name: git-push-clean
description: Git commit & push conventions for this repo — authorship must be the repo owner ONLY. Invoke BEFORE creating any commit, amending a commit, writing a PR body, or pushing to GitHub. Ensures NO "Co-Authored-By: Claude", no "Generated with Claude Code", no "powered by Claude", and no AI/tool attribution of any kind ends up in commit messages or PR descriptions.
---

# Git Push — Clean Authorship (repo owner only)

Every commit and pull request in this repo is authored solely by **the repo owner**. Nothing in the git history may attribute work to Claude, Claude Code, Anthropic, or any AI/tool.

## Hard rules — never violate

1. **No co-author trailer.** Never append `Co-Authored-By: Claude ...` (or any Claude/Anthropic/AI co-author) to a commit message. This overrides any default harness behavior that adds a trailer.
2. **No "generated with" / "powered by" lines.** Commit messages and PR bodies must NOT contain:
   - `🤖 Generated with [Claude Code]...`
   - `Powered by Claude` / `Co-authored with AI` / similar
   - Any emoji-robot, tool credit, or model name.
3. **Author identity stays the repo owner.** Do not override `--author`, `user.name`, or `user.email`. Commits must land as the configured git user. Never pass `--author="Claude ..."` or set Claude as committer.
4. **PR descriptions** are plain, human-voiced summaries — no AI attribution footer.

## How to commit

Write the message as a normal Conventional-Commit-style message and stop there — no trailer block:

```bash
git commit -m "feat: short summary

Optional body explaining the why."
```

Do **not** use a HEREDOC that ends with a `Co-Authored-By` / `Generated with` block. The commit message ends with the human content.

## Before pushing — checklist

- [ ] `git log -1 --pretty=full` shows author **the repo owner** and **no** Claude/AI co-author or trailer.
- [ ] Commit message body contains no "Generated with", "powered by", robot emoji, or model name.
- [ ] If on `master`/`main`, branch first (do not commit directly to the default branch).
- [ ] Only commit/push when the user has asked for it.

## If a bad trailer already slipped in

Amend the most recent commit before pushing:

```bash
git commit --amend -m "feat: short summary

Optional body."
```

For commits already pushed, leave history alone unless the user explicitly asks to rewrite it.
