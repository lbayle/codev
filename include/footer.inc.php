<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?php

# DEBUG
#echo "<br/>";
#IssueCache::getInstance()->displayStats();
#echo "<br/>";
#UserCache::getInstance()->displayStats();
#echo "<br/>";
#ProjectCache::getInstance()->displayStats();
#echo "<br/>";
#TimeTrackCache::getInstance()->displayStats();

mysql_close($bugtracker_link);
?>


<br/><hr />
<address class="right" title='Freedom is nothing else but a chance to be better. (Albert Camus)'>
<?php
   # La liberte n'offre qu'une chance d'etre meilleur, la servitude n'est que la certitude de devenir pire.  (Albert Camus)
   echo "<a href='http://www.gnu.org/licenses/gpl.html' target='_blank'><img title='GPL v3' src='".getServerRootURL()."/images/copyleft.png' /></a>";
   echo "2010-".date("Y")."&nbsp; Louis BAYLE<br/>Designed for Firefox";
?>
</address>

</body>
</html>