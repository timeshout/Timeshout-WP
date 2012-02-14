
<?php if(isset($xml) AND $xml->events):?>
<?php foreach($xml->events->event as $event):?>
<div class="event">
<div class="title"><a href="<?=$event->url?>" target="_blank" title="View event details in a new window"><?=$event->title;?></a></div>
<div class="info"><?=render_dates($event)?></div>
<div class="summary"><?=$event->summary?> <a href="<?=$event->url?>" target="_blank" title="View event details in a new window">read more</a></div>
</div>

<?php endforeach;?>
<?php else:?>
    <div class="empty">No events found</div>
<?php endif;?>

<a id="timeshout" href="http://timeshout.com" title="Timeshout is a place that lets you post events, create calendars, sell tickets and share with the world">
    <img src="http://timeshout.com/images/logo/small.png" height="25" width="100" border="0"/>
</a>
