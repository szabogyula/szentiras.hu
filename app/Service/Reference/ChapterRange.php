<?php

namespace SzentirasHu\Service\Reference;

class ChapterRange
{
    /**
     * @var ChapterRef
     */
    public $chapterRef;
    /**
     * @var ChapterRef
     */
    public $untilChapterRef;

    public function __construct(ChapterRef $chapterRef, ChapterRef $untilChapterRef = null) {
        $this->chapterRef = $chapterRef;
        $this->untilChapterRef = $untilChapterRef;
    }

    public function toString()
    {
        $s = $this->chapterRef->toString();
        if ($this->untilChapterRef) {
            $s .= "-{$this->untilChapterRef->toString()}";
        }
        return $s;
    }

    public function hasVerse($chapter, $verse)
    {
        if (is_string($verse)) {
            $verse = (int) $verse;
        }
            // inside range (regardless of verse)
        if ($chapter > $this->chapterRef->chapterId && $this->untilChapterRef && $chapter < $this->untilChapterRef->chapterId) {
            return true;
        }
        // outside range (regardless of verse)
        if ($chapter < $this->chapterRef->chapterId
            || $chapter > $this->chapterRef->chapterId && !$this->untilChapterRef
            || $this->untilChapterRef && $chapter > $this->untilChapterRef->chapterId
        ) {
            return false;
        }
        // if no verses, all verses are good.
        if (count($this->chapterRef->verseRanges) === 0) {
            return true;
        }
        // can be inside range depending on verse
        if ($chapter == $this->chapterRef->chapterId) {
            foreach ($this->chapterRef->verseRanges as $verseRange) {
                if ($verseRange->contains($verse)) {
                    return true;
                }
            }
        }

        if ($this->untilChapterRef) {
            if ($chapter == $this->chapterRef->chapterId) {
                if ($verse >= last($this->chapterRef->verseRanges)->verseRef->verseId) {
                    return true;
                }
            } else if ($chapter == $this->untilChapterRef->chapterId) {
                if ($verse <= head($this->untilChapterRef->verseRanges)->untilVerseRef->verseId) {
                    return true;
                }
            } else if ($chapter > $this->chapterRef->chapterId && $chapter < $this->untilChapterRef->chapterId) {
                return true;
            }
        }
        return false;
    }
}
