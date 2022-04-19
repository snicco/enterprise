module.exports = {
    "branches": [
        "master"
    ],
    "repositoryUrl": "https://github.com/VENDOR_SLUG/VENDOR_SLUG.git",
    "tagFormat": "v${version}",
    "plugins": [
        ["@semantic-release/commit-analyzer", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            }
        }],
        ["@semantic-release/release-notes-generator", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            },
            "writerOpts": {
                groupBy: 'type',
                commitGroupsSort: 'title',
                commitsSort: ['scope', 'subject'],
                noteGroupsSort: 'title',
            }
        }],
        ["@semantic-release/exec", {
            "prepareCmd": "./bin/prepare-release.sh ${nextRelease.version}",
        }],
        "@semantic-release/changelog",
        ["@semantic-release/github", {
            "assets": [
                {"path": "dist/PLUGIN_BASENAME", "label": "VENDOR_TITLE plugin"},
            ]
        }],
        ["@semantic-release/git", {
            "assets": [
                "CHANGELOG.md",
            ],
            "message": "chore: release v${nextRelease.version}"
        }]
    ]
}