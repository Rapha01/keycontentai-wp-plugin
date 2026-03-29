### Workflow for releasing a new version to WordPress.org SVN (TortoiseSVN GUI)

Assume your current date is **2026-03-23** and you develop in your local WP install, then publish to SVN.

## 0) Decide the release version
Example: `1.2.3`

Before touching SVN, update in your **local plugin**:
- Main plugin header: `Version: 1.2.3`
- `readme.txt`: set `Stable tag: 1.2.3` (recommended) and update `Changelog`

## 1) Update your SVN working copy
Go to your SVN working copy root (contains `trunk`, `tags`, maybe `assets`), e.g.:
`C:\wp-svn\YOUR-PLUGIN-SLUG\`

1. Right‑click the root folder → **SVN Update**
   - This ensures you’re not working on an outdated working copy.

## 2) Copy your local plugin into `trunk`
Copy **the contents** of your local plugin folder into:
`...\YOUR-PLUGIN-SLUG\trunk\`

Make sure you don’t create an extra nested folder (common mistake).

## 3) Tell SVN about new/deleted files
1. Right‑click `trunk` → **TortoiseSVN → Check for modifications**
2. In that window:
   - Select items marked **unversioned** (`?`) → right‑click → **Add**
   - For files you removed, make sure they’re deleted *inside the working copy* so SVN shows them as **missing**; then commit will remove them (or right-click → delete, then commit).

## 4) Commit `trunk` (upload the release contents)
1. Right‑click `trunk` → **SVN Commit…**
2. Message example: `Prepare release 1.2.3`
3. Commit

At this point, the new version is in `trunk` on the server.

## 5) Tag the release (server-side copy)
1. Right‑click `trunk` → **TortoiseSVN → Branch/Tag…**
2. **To path:** `/tags/1.2.3`
3. Select **HEAD revision** (or “Working copy” only if you know why—HEAD is typical right after committing)
4. Message: `Tag 1.2.3`
5. OK

This creates `tags/1.2.3` on the server without re-uploading everything.

## 6) Verify the tag exists (recommended)
- Right‑click repo root → **Repo-browser** → check `/tags/1.2.3/`, or
- Right‑click repo root → **Show log** and confirm the “Tag 1.2.3” revision exists

## 7) (Optional but recommended) Bump trunk to next dev version
After tagging, some people immediately update trunk’s plugin header/readme to something like:
- `Version: 1.2.4`
- `Stable tag: 1.2.3` (leave stable pointing to the released tag)

Then commit with message: `Begin 1.2.4 development`

---

### The 2 most common gotchas
1) **Forgetting to Add**: use “Check for modifications” and add unversioned files from there.
2) **Wrong Stable tag**: if `Stable tag` doesn’t match the tag version, WordPress.org may serve the wrong code.

If you tell me your plugin slug + what version you’re releasing next, I’ll tailor the exact commit/tag messages and a quick verification checklist (“these 3 files must be present in the tag”).