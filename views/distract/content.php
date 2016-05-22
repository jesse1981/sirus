<div class="main">
    <div class="list-wrapper">
        <div class="list-item">
            <h3>Games</h3>
            <select id="games">
                <option value="0">-- Select One --</option>
                <option value="tennis">Table Tennis</option>
                <option value="sonicplay">Sonic</option>
                <option value="7bass-fis">Fishing</option>
                <option value="zombie-racer">Zombie Racer</option>
                <option value="poll">Billiards</option>
                <option value="superflashmariobros">Super Mario</option>
                <option value="egyptian-horse">Egyptian Horse</option>
                <option value="">Angry Birds Halloween</option>
                <option value="angry"></option>
                <option value="kingdom-bow">Kingdom Bow</option>
            </select>
        </div>
        <div class="list-item">
            <h3>Youtube</h3>
            <select id="youtube">
                <option value="0">-- Select One --</option>
                <option value="PL988EAD3E24365240">Meditation</option>
                <option value="PLU74Y_bNwfvTEP9pUTuDmOiuSbBc8trLk">Comedy Sketches</option>
            </select>
        </div>
        <div class="list-item">
            <h3>Spotify</h3>
            <select id="spotify">
                <option value="0">-- Select One --</option>
                <option value="jonahbergher%3Aplaylist%3A4kJbqwOQzNlyy4lOJ2jYXP">Meditation</option>
                <option value="spotify%3Aplaylist%3A4hOKQuZbraPDIfaGbM3lKI">Top 100</option>
            </select>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#games').change(function() {
        var obj = $(this).children(':selected');
        if (obj.attr('value')!=="0") {
            var value = obj.attr('value');
            window.location('/distract/game/'+value);
        }
    });
    $('#youtube').change(function() {
        var obj = $(this).children(':selected');
        if (obj.attr('value')!=="0") {
            var value = obj.attr('value');
            window.location('/distract/youtube/'+value);
        }
    });
    $('#spotify').change(function() {
        var obj = $(this).children(':selected');
        if (obj.attr('value')!=="0") {
            var value = obj.attr('value');
            window.location('/distract/spotify/'+value);
        }
    });
});    
</script>