<?php

// This page will recreate all indexes from the structure.php file.
// It can be called from your phpserver: ~/lists/admin/?page=reindex
// PHP will skip indexes that are already created by name.
// WARNING: This can take a long time on large tables, there is no feedback
// and te session can be killed by your browser or server after a timeout.
// Just reload if you think nothing happens after 30 minutes or so.

@ob_end_flush();
include dirname(__FILE__).'/structure.php';

echo '<ul>';

foreach ($DBstruct as $table => $columns) {
    echo '<li><h3>'.$table.'</h3><br/><ul>';
    cl_output($GLOBALS['I18N']->get('processing ').$table);
    foreach ($columns as $column => $definition) {
        if (strpos($column, 'index') === 0) {
            printf('<li>'.$GLOBALS['I18N']->get('Adding index <b>%s</b> to %s</li>'), $definition[0], $table);
            cl_output(sprintf($GLOBALS['I18N']->get('Adding index <b>%s</b> to %s<br/>'), $definition[0], $table));
            flush();
            // ignore errors, which are most likely that the index already exists
            Sql_Query(sprintf('alter table %s add index %s', $table, $definition[0]), 1);
        } elseif (strpos($column, 'unique') === 0) {
            printf('<li>'.$GLOBALS['I18N']->get('Adding unique index <b>%s</b> to %s</li>'), $definition[0], $table);
            cl_output(sprintf($GLOBALS['I18N']->get('Adding unique index <b>%s</b> to %s<br/>'), $definition[0],
                $table));
            flush();
            // ignore errors, which are most likely that the index already exists
            //# hmm, mysql seems to create a new one each time
            //# that's when they're not "named" in the structure -> fix

            Sql_Query(sprintf('alter table %s add unique %s', $table, $definition[0]), 1);
        }
    }
    echo '</ul></li>';
}
echo '</ul>';
