<div class="article-meta mb-4 flex items-end justify-between">
    @if ($withAuthor && $author)
        <x-capell-blog::page.author :$author />
    @endif

    @if ($tags->isNotEmpty())
        <div
            class="article-tags flex flex-col items-center gap-x-10 gap-y-6 md:flex-row md:justify-between lg:flex-row-reverse"
        >
            <x-capell-blog::page.tags
                :tagPage="$tagPage"
                :tags="$tags"
                with_tag_icon="true"
            />
        </div>
    @endif
</div>
