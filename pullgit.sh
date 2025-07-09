#!/bin/bash
BRANCH="main"
git fetch origin $BRANCH
git diff --name-only HEAD origin/$BRANCH | xargs -I{} git checkout origin/$BRANCH -- {}