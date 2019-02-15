<div
    id="grid-facebook-post-<?php echo $item->id; ?>"
    class="fb-post"
    data-href="https://www.facebook.com/<?php echo $parts[0] ?>/posts/<?php echo $parts[1]; ?>/"
    data-show-text="true"
>
    <blockquote
        cite="https://www.facebook.com/<?php echo $parts[0] ?>/posts/<?php echo $parts[1]; ?>/"
        class="fb-xfbml-parse-ignore"
    >
        <?php if("" != $page): ?>
            Posted by <a
                href="https://www.facebook.com/facebook/"><?php echo $page; ?></a>
            on&nbsp;<a
                href="https://www.facebook.com/20531316728/posts/10154009990506729/"><?php echo $item->created_time; ?></a>
        <?php endif; ?>
    </blockquote>
</div>
