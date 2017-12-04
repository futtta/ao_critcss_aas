#!/bin/sh

if [ ! $(which cloc) ]; then
  echo "cloc (Count Lines of Code) is not installed in this system."
  echo "Please install it and try again. See https://github.com/AlDanial/cloc#Overview"
  exit 1
fi

if [ ! -f FILELIST.txt ]; then
  echo "FILELIST.txt not found."
  exit 2
fi

STAT_SUM=$(cat FILELIST.txt | xargs -d \\n du -c | grep total | cut -f1)

STATS="*Stats updated at: $(date +%Y/%m/%d)"$'*\n'$'\n'
STATS+="**Project size:** $STAT_SUM KB"$'\n'$'\n'
STATS+="### Lines of Code"$'\n'$'\n'
STATS+="**Language**|**Files**|**Blank Lines**|**Comments**|**Functional Code**"$'\n'
STATS+=$(cloc --list-file=FILELIST.txt --force-lang=PHP,module --force-lang=PHP,theme --force-lang="Bourne Shell",conf \
          --force-lang="Bourne Shell",types --lang-no-ext="Bourne Shell" --md | tail -n +6 | \
          sed "s/^\(SUM:\)|\([0-9]*\)|\([0-9]*\.[0-9]*\)|\([0-9]*\.[0-9]*\)|\([0-9]*\)/**TOTAIS:**|**\2**|**\3**|**\4**|**\5**/g")
STATS+=$'\n'$'\n'"### Media and Other"$'\n'$'\n'
STATS+="**Type**|**Files**|**Size (B)**"$'\n'

GIF_FILES=$(cat FILELIST.txt | grep '.gif' | wc -l)
GIF_SIZE=$(cat FILELIST.txt | grep '.gif' | xargs -d \\n du -b -c | grep total | cut -f1)

PNG_FILES=$(cat FILELIST.txt | grep '.png' | wc -l)
PNG_SIZE=$(cat FILELIST.txt | grep '.png' | xargs -d \\n du -b -c | grep total | cut -f1)

FILE_SUM=$((GIF_FILES + PNG_FILES))
SIZE_SUM=$((GIF_SIZE + PNG_SIZE))

STATS+=":-------|-------:|-------:"$'\n'
STATS+="GIF|$GIF_FILES|$GIF_SIZE"$'\n'
STATS+="PNG|$PNG_FILES|$PNG_SIZE"$'\n'
STATS+="--------|--------|--------"$'\n'
STATS+="**SUM:**|**$FILE_SUM**|**$SIZE_SUM**"

SPEC_ITEMS=$(grep -nr "NOTE: implements section" --exclude=\*.sh | sort -h)

STATS+=$'\n'$'\n'"### Spec Items"$'\n'$'\n'
STATS+='```'$'\n'
STATS+="$SPEC_ITEMS"$'\n'
STATS+='```'

OUTSCOPE_ITEMS=$(grep -n -r "NOTE: out of scope" --exclude=\*.sh | sort -h)

STATS+=$'\n'$'\n'"### Out of Scope Items"$'\n'$'\n'
STATS+='```'$'\n'
STATS+="$OUTSCOPE_ITEMS"$'\n'
STATS+='```'

echo "$STATS"