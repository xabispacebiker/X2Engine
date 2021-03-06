<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

class CutDownPermissionsForestTest extends X2DbTestCase {

    /**
     * Contains dump of database at 4.1.7 Platinum after adding custom roles called TestRole and
     * SuperTestRole. SuperTestRole is flagged as Admin
     */
    public $fixtures = array (
        'authItem' => array (':x2_auth_item', '.CutDownPermissionsForestTest'), 
        'fields' => array ('Fields', '.CutDownPermissionsForestTest'), 
        'dropdowns' => array ('Dropdowns', '.CutDownPermissionsForestTest'), 
        'authItemChild' => array (':x2_auth_item_child', '.CutDownPermissionsForestTest'), 
    );

    /**
     * Runs 4.2b migration scripts on auth tables with custom roles set up in version 4.1.7. 
     * Asserts that roles are restructured to match expectations of 4.2b permissions system.
     */
    public function testRestructuringOfCustomRole () {

         // Get all children of test role before migration scripts run 
        $testRoleAuthItemChildren = array_map (function ($row) {
            return $row['child'];
        }, Yii::app()->db->createCommand ("
            select * 
            from x2_auth_item_child
            where parent='TestRole'
        ")->queryAll ());
        print_r ($testRoleAuthItemChildren);

        // run first migration script
        $command = Yii::app()->basePath . '/yiic runmigrationscript ' .
            'migrations/4.2b/1407436318-update-sql.php';
        $return_var;
        $output = array ();
        print_r (exec ($command, $return_var, $output));
        print_r ($return_var);
        print_r ($output);

        // run second migration script
        $command = Yii::app()->basePath . '/yiic runmigrationscript ' .
            'migrations/4.2b/1406225725-cut-down-permissions-forest.php';
        $return_var;
        $output = array ();
        print_r (exec ($command, $return_var, $output));
        print_r ($return_var);
        print_r ($output);

         // Get all children of test role after migration scripts run
        $newTestRoleAuthItemChildren = array_map (function ($row) {
            return $row['child'];
        }, Yii::app()->db->createCommand ("
            select * 
            from x2_auth_item_child
            where parent='TestRole'
        ")->queryAll ());
        print_r ($newTestRoleAuthItemChildren);

        // ensure that permisions were added properly by migration scripts
        foreach ($testRoleAuthItemChildren as $child) {
            println ('asserting correctness of restructured permissions for '.$child);
            $module = preg_replace (
                '/(PrivateReadOnly|PrivateUpdate|PrivateFull|PrivateBasic|ReadOnly|Basic|Update|'.
                'Full|Admin)Access$/', '', $child);
            if (preg_match ('/Charts|Reports/', $child)) 
                continue;
            if (!preg_match ('/.*(((Private)?(Basic|Full|Update))|Admin)Access$/', $child)) 
                continue;

            $this->assertContains ($module.'ReadOnlyAccess', $newTestRoleAuthItemChildren);
            if (preg_match ('/.*(Private)?BasicAccess$/', $child)) {
                continue;
            }
            $this->assertContains ($module.'BasicAccess', $newTestRoleAuthItemChildren);
            if (preg_match ('/.*(Private)?UpdateAccess$/', $child)) {
                continue;
            }
            $this->assertContains ($module.'UpdateAccess', $newTestRoleAuthItemChildren);
            if (preg_match ('/.*(Private)?FullAccess$/', $child)) {
                continue;
            }
            $this->assertContains ($module.'FullAccess', $newTestRoleAuthItemChildren);
            $this->assertEquals (1, preg_match ('/.*AdminAccess$/', $child));
        }
    }

}


?>
