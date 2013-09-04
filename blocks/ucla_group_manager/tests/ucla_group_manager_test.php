<?php

/**
 * Unit Test for ucla_group_manager block
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');

class ucla_group_manager_testcase extends advanced_testcase {
    /**
     * Stores mocked version of ucla_group_manager.
     */
    private $mockgroupmanager = null;    

    /**
     * Used by mocked_query_registrar to return data for a given stored 
     * procedure, term, and srs.
     * @var array
     */
    private $mockregdata = array();

    /**
     * Stubs the query_registrar method of ucla_group_manager class,
     * so we aren't actually making a live call to the Registrar.
     *
     * Must call set_mockregdata() beforehand to set what data should be
     * returned.
     *
     * @param string $sp        Stored procedure to call.
     * @param string $term
     * @param bool $filter
     *
     * @return array            Returns corresponding value in $mockregdata.
     */
    public function mocked_query_registrar($sp, $reqArr, $filter) {
        /* The $mockregdata array is indexed as follows:
         *  [storedprocedure] => [term] => [srs] => [array of results]
         */
        return $this->mockregdata[$sp][$reqArr[0]][$reqArr[1]];
    }

    /**
     * Prepares data that will be returned by mocked_query_registrar.
     *
     * @param string $sp
     * @param string $term
     * @param string $srs
     * @param array $results
     */
    protected function set_mockregdata($sp, $term, $srs, array $results) {
        $this->mockregdata[$sp][$term][$srs] = $results;
    }

    /**
     * Set up registrar_query() stub. 
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Only stub the query_registrar method.
        $this->mockgroupmanager = $this->getMockBuilder('ucla_group_manager')
                ->setMethods(array('query_registrar'))
                ->getMock();

        // Method $this->mocked_query_registrar will be called instead of
        // local_ucla_enrollment_helper->query_registrar.
        $this->mockgroupmanager->expects($this->any())
                ->method('query_registrar')
                ->will($this->returnCallback(array($this, 'mocked_query_registrar')));

        // Remove any previous registrar data.
        unset($this->mockregdata);
        $this->mockregdata = array();
    }

    /**
     * Test that student is unenrolled from previous section and enrolled
     * in new section when student switches to another section via registrar.
     * In this test case the registrar returns the student as having
     * dropped the class with enrollment status 'D'.  Student A will start in
     * section A and student B will begin in section B.  This will test that 
     * student A can correctly transfer into section B.
     */
    public function test_sync_course() {
        // Create a non-crosslisted class.
        $courses = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_class(array());

        $class = array_pop($courses);
        $term = $class->term;
        $srs = $class->srs;
        $courseid = $class->courseid;

        // Create students that will switch sections.
        $studentA = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();
        $studentB = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();

        // Enroll students in the course.
        $this->getDataGenerator()->enrol_user($studentA->id, $courseid);
        $this->getDataGenerator()->enrol_user($studentB->id, $courseid);

        // Set up mock sections.
        $students = array();
        $students['studentA'] = $studentA;
        $students['studentB'] = $studentB;

        $sections['001A'] = array('sect_no' => '001A',
                                  'srs_crs_no' => $srs-800);

        $sections['001B'] = array('sect_no' => '001B',
                                  'srs_crs_no' => $srs-700);

        // Set up mock data for ccle_class_sections.
        $sectionresults = array();
        foreach ($sections as $section) {
            $sectionresults[] = array('sect_no' => $section['sect_no'],
                                      'cls_act_typ_cd' => 'DIS',
                                      'sect_enrl_stat_cd' => 'O',
                                      'srs_crs_no' => $section['srs_crs_no']);           
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs, $sectionresults);

        // Set up mock data for ccle_roster_class for class.
        $classroster = array();
        foreach ($students as $bol => $student) {
            $classroster[] = array('term_cd' => $term,
                                   'stu_id' => $student->idnumber,
                                   'full_name_person' => $student->firstname.' '.$student->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $student->email,
                                   'bolid' => $bol); 
        }
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Set up mock data for ccle_roster_class for each section.
        // Student A will be in section A.
        $sectionrosterA = array();
        $sectionrosterA[0] = array('term_cd' => $term,
                                   'stu_id' => $studentA->idnumber,
                                   'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $studentA->email,
                                   'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001A']['srs_crs_no'], $sectionrosterA);

        // Student B will be in section B.
        $sectionrosterB = array();
        $sectionrosterB[0] = array('term_cd' => $term,
                                   'stu_id' => $studentB->idnumber,
                                   'full_name_person' => $studentB->firstname.' '.$studentB->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $studentB->email,
                                   'bolid' => 'studentB');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001B']['srs_crs_no'], $sectionrosterB);       

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupA = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001A']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEquals(array($studentA->id => $studentA->id), $groupA->memberships);

        $groupB = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001B']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEquals(array($studentB->id => $studentB->id), $groupB->memberships);

        unset($groupA);
        unset($groupB);

        // Change student enrollments so that both are in section B.
        // Set student A to have 'D' enrollment status in sectionA.
        $sectionrosterA[0] = array('term_cd' => $term,
                                   'stu_id' => $studentA->idnumber,
                                   'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                   'enrol_stat_cd' => 'D',
                                   'ss_email_addr' => $studentA->email,
                                   'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001A']['srs_crs_no'], $sectionrosterA);

        // Add studentA to section B.
        $sectionrosterB[1] = array('term_cd' => $term,
                                   'stu_id' => $studentA->idnumber,
                                   'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $studentA->email,
                                   'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001B']['srs_crs_no'], $sectionrosterB);

        // Sync changes to groups.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupA = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001A']['srs_crs_no'],
                                              'courseid' => $courseid));

        // Check that section A is empty.
        $this->assertEmpty($groupA->memberships);

        $groupB = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001B']['srs_crs_no'],
                                              'courseid' => $courseid));

        // Check that section B has students A and B.
        $this->assertEquals(array($studentB->id => $studentB->id,
                                  $studentA->id => $studentA->id), $groupB->memberships);
    }

    /**
     * Test that student is unenrolled from previous section when student 
     * switches sections and eventually drops the course via registrar.
     * In this test case the registrar does NOT return the student in the
     * roster for the section or course which was dropped.
     */
    public function test_sync_course2() {
        // Create a non-crosslisted class.
        $course = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_class(array());

        $class = array_pop($course);
        $term = $class->term;
        $srs = $class->srs;
        $courseid = $class->courseid;

        // Create a student that will drop the class.
        $studentA = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();

        // Enroll student in the course.
        $this->getDataGenerator()->enrol_user($studentA->id, $courseid);

        // Set up mock sections.
        $sections['001A'] = array('sect_no' => '001A',
                                  'srs_crs_no' => $srs-800);

        $sections['001B'] = array('sect_no' => '001B',
                                  'srs_crs_no' => $srs-700);

        // Set up mock data for ccle_class_sections.
        $sectionresults = array();
        foreach ($sections as $section) {
            $sectionresults[] = array('sect_no' => $section['sect_no'],
                                      'cls_act_typ_cd' => 'DIS',
                                      'sect_enrl_stat_cd' => 'O',
                                      'srs_crs_no' => $section['srs_crs_no']);           
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs, $sectionresults);

        // Set up mock data for ccle_roster_class for class.
        $classroster = array();
        $classroster[0] = array('term_cd' => $term,
                                'stu_id' => $studentA->idnumber,
                                'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                'enrol_stat_cd' => 'E',
                                'ss_email_addr' => $studentA->email,
                                'bolid' => 'studentA'); 

        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Set up mock data for ccle_roster_class for each section.
        // Student A is enrolled in section A initially.
        $sectionrosterA = array();
        $sectionrosterA[0] = array('term_cd' => $term,
                                   'stu_id' => $studentA->idnumber,
                                   'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $studentA->email,
                                   'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001A']['srs_crs_no'], $sectionrosterA);

        // Section B is empty.
        $sectionrosterB = array();
        $this->set_mockregdata('ccle_roster_class', $term, $sections['001B']['srs_crs_no'], $sectionrosterB);       

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupA = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001A']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEquals(array($studentA->id => $studentA->id), $groupA->memberships);

        $groupB = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001B']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEmpty($groupB->memberships);

        unset($groupA);
        unset($groupB);

        // Student A now switches frmom section A to B.
        // Section A is empty.
        unset($sectionrosterA[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $sections['001A']['srs_crs_no'], $sectionrosterA);

        // Section B contains student A.
        $sectionrosterB[0] = array('term_cd' => $term,
                                   'stu_id' => $studentA->idnumber,
                                   'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                   'enrol_stat_cd' => 'E',
                                   'ss_email_addr' => $studentA->email,
                                   'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections['001B']['srs_crs_no'], $sectionrosterB);

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupA = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001A']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEmpty($groupA->memberships);

        $groupB = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001B']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEquals(array($studentA->id => $studentA->id), $groupB->memberships);

        unset($groupA);
        unset($groupB);

        // Class roster is empty.
        unset($classroster[0]); 
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Section rosters are now empty.
        unset($sectionrosterB[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $sections['001B']['srs_crs_no'], $sectionrosterB);

        // Sync the groups and check that the rosters are correctly configured
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupA = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001A']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEmpty($groupA->memberships);

        $groupB = new ucla_synced_group(array('term' => $term,
                                              'srs' => $sections['001B']['srs_crs_no'],
                                              'courseid' => $courseid));

        $this->assertEmpty($groupB->memberships);
    }

    /**
     * Tests that a student is able to switch sections within a crosslisted
     * course. Student starts in section 1A and switches to section 2B where
     * 1 and 2 are crosslisted courses.
     */
    public function test_sync_course_crosslisted() {
        // Create crosslisted courses.
        $crosslisted = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_class(array(array(), array()));

        // Pop classes from back of return array.
        $class2 = array_pop($crosslisted);
        $class1 = array_pop($crosslisted);    

        $term = $class1->term;
        $srs1 = $class1->srs;
        $courseid1 = $class1->courseid;

        // Expect term to be the same for class 2.
        $srs2 = $class2->srs;
        $courseid2 = $class2->courseid;

        // Create a student that will switch sections.
        $studentA = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();

        // Enroll student in class 1 initially.
        $this->getDataGenerator()->enrol_user($studentA->id, $courseid1, null, 'manual');

        // Set up mock sections.
        // Class 1 sections.
        $sections1 = array();
        $sections1['001A'] = array('sect_no' => '001A',
                                   'srs_crs_no' => $srs1-800);

        $sections1['001B'] = array('sect_no' => '001B',
                                   'srs_crs_no' => $srs1-700);

        // Class 2 sections.
        $sections2 = array();
        $sections2['002A'] = array('sect_no' => '002A',
                                   'srs_crs_no' => $srs2-800);

        $sections2['002B'] = array('sect_no' => '002B',
                                   'srs_crs_no' => $srs2-700);

        // Set up mock data for ccle_class_sections for each class.
        $sectionresults1 = array();
        foreach ($sections1 as $section) {
            $sectionresults1[] = array('sect_no' => $section['sect_no'],
                                       'cls_act_typ_cd' => 'DIS',
                                       'sect_enrl_stat_cd' => 'O',
                                       'srs_crs_no' => $section['srs_crs_no']);           
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs1, $sectionresults1);

        $sectionresults2 = array();
        foreach ($sections2 as $section) {
            $sectionresults2[] = array('sect_no' => $section['sect_no'],
                                       'cls_act_typ_cd' => 'DIS',
                                       'sect_enrl_stat_cd' => 'O',
                                       'srs_crs_no' => $section['srs_crs_no']);           
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs2, $sectionresults2);

        // Set up mock data for ccle_roster_class for each class.
        $classroster1 = array();
        $classroster1[] = array('term_cd' => $term,
                                'stu_id' => $studentA->idnumber,
                                'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                'enrol_stat_cd' => 'E',
                                'ss_email_addr' => $studentA->email,
                                'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $srs1, $classroster1);

        $classroster2 = array(); 
        $this->set_mockregdata('ccle_roster_class', $term, $srs2, $classroster2);

        // Set up mock data for ccle_roster_class for each section.
        $sectionroster1A = array();
        $sectionroster1A[0] = array('term_cd' => $term,
                                    'stu_id' => $studentA->idnumber,
                                    'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                    'enrol_stat_cd' => 'E',
                                    'ss_email_addr' => $studentA->email,
                                    'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections1['001A']['srs_crs_no'], $sectionroster1A);

        $sectionroster1B = array();
        $this->set_mockregdata('ccle_roster_class', $term, $sections1['001B']['srs_crs_no'], $sectionroster1B);

        $sectionroster2A = array();
        $this->set_mockregdata('ccle_roster_class', $term, $sections2['002A']['srs_crs_no'], $sectionroster2A);  

        $sectionroster2B = array();
        $this->set_mockregdata('ccle_roster_class', $term, $sections2['002B']['srs_crs_no'], $sectionroster2B);  

        // Sync the groups and check that the rosters are correctly configured.
        $sync1 = $this->mockgroupmanager->sync_course($courseid1);
        $this->assertTrue($sync1);

        $group1A = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections1['001A']['srs_crs_no'],
                                               'courseid' => $courseid1));

        // Student should be enrolled in section 1A.
        $this->assertEquals(array($studentA->id => $studentA->id), $group1A->memberships);

        $group1B = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections1['001B']['srs_crs_no'],
                                               'courseid' => $courseid1));

        $this->assertEmpty($group1B->memberships);

        $sync2 = $this->mockgroupmanager->sync_course($courseid2);
        $this->assertTrue($sync2);

        $group2A = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections2['002A']['srs_crs_no'],
                                               'courseid' => $courseid2));

        $this->assertEmpty($group2A->memberships);

        $group2B = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections2['002B']['srs_crs_no'],
                                               'courseid' => $courseid2));

        $this->assertEmpty($group2B->memberships);

        unset($group1A);
        unset($group1B);
        unset($group2A);
        unset($group2B);

        // Change student enrollments so that student is in class 2 section 2B.
        // Unenroll student from class 1 and enroll them in class 2.
        $enrol = enrol_get_plugin('manual');
        $instances = enrol_get_instances($courseid1, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == 'manual') {
                $enrol->unenrol_user($instance, $studentA->id);
                break;
            }
        }

        $this->getDataGenerator()->enrol_user($studentA->id, $courseid2);

        // Remove student from class 1 and section 1A in mock data.
        unset($classroster1[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $srs1, $classroster1);
        unset($sectionroster1A[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $sections1['001A']['srs_crs_no'], $sectionroster1A);

        // Add student to class 2 and section 2B in mock data.
        $classroster2[0] = array('term_cd' => $term,
                                 'stu_id' => $studentA->idnumber,
                                 'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                 'enrol_stat_cd' => 'E',
                                 'ss_email_addr' => $studentA->email,
                                 'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $srs2, $classroster2);

        $sectionroster2B[0] = array('term_cd' => $term,
                                    'stu_id' => $studentA->idnumber,
                                    'full_name_person' => $studentA->firstname.' '.$studentA->lastname,
                                    'enrol_stat_cd' => 'E',
                                    'ss_email_addr' => $studentA->email,
                                    'bolid' => 'studentA');

        $this->set_mockregdata('ccle_roster_class', $term, $sections2['002B']['srs_crs_no'], $sectionroster2B);

        // Sync the groups and check that the rosters are correctly configured.
        $sync1 = $this->mockgroupmanager->sync_course($courseid1);
        $this->assertTrue($sync1);

        $group1A = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections1['001A']['srs_crs_no'],
                                               'courseid' => $courseid1));

        // Class 1 section A is now empty.
        $this->assertEmpty($group1A->memberships);

        $group1B = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections1['001B']['srs_crs_no'],
                                               'courseid' => $courseid1));

        $this->assertEmpty($group1B->memberships);

        $sync2 = $this->mockgroupmanager->sync_course($courseid2);
        $this->assertTrue($sync2);

        $group2A = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections2['002A']['srs_crs_no'],
                                               'courseid' => $courseid2));

        $this->assertEmpty($group2A->memberships);

        $group2B = new ucla_synced_group(array('term' => $term,
                                               'srs' => $sections2['002B']['srs_crs_no'],
                                               'courseid' => $courseid2));

        // Class 2 section B now has a student.
        $this->assertEquals(array($studentA->id => $studentA->id), $group2B->memberships);
    }
}
?>

