<?php // $Id: block_elluminate.php,v 1.1.2.2 2009/03/18 16:45:57 mchurch Exp $

/**
 * Blackboard Collaborate block.
 *
 * Allows students to manage their user information on the Blackboard Collaborate
 * server from Moodle and admins/teachers to add students and other users
 * to a remote Blackboard Collaborate server.
 *
 * @version $Id: block_elluminate.php,v 1.1.2.2 2009/03/18 16:45:57 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


class block_elluminate extends block_list {

	public $content = NULL;

	function init() {
		$this->title = get_string('elluminate', 'block_elluminate');
	}

	function display_block_error_msg($msg) {
		global $OUTPUT;
		$this->content->icons[] = '<img src="'.$OUTPUT->pix_url('icon', 'elluminate') . '" class="icon" alt="" />&nbsp;';
		$this->content->items[]= ' <p>'.$msg.'</p>';
		return $this->content;
	}

	function get_content() {
		global $CFG, $USER, $DB, $OUTPUT, $COURSE, $ELLUMINATE_CONTAINER;
		

		require_once($CFG->dirroot . '/mod/elluminate/lib.php');
		
		$logger = Elluminate_Logger_Factory::getLogger("block_elluminate");

		if ($this->content !== NULL) {
			return $this->content;
		}
		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		if (!isloggedin() || empty($this->instance)) {
			return $this->content;
		}

		try {
			$sessionDAO = new Elluminate_Session_DAO();
			$sessions = $sessionDAO->getSessionsWithRecentRecordings($COURSE->id);
		} catch (Elluminate_Exception $e) {
			return $this->display_block_error_msg("Session Load Error: " . get_string($e->getUserMessage(), 'elluminate'));
		} catch (Exception $e) {
			return $this->display_block_error_msg("Session Load Error: " . get_string('user_error_processing', 'elluminate'));
		} 

		$this->content->items[] = '<b>' . get_string('recentrecordings', 'block_elluminate') . ':</b>';
		$this->content->icons[] = '';
		$count = 0;
		foreach ($sessions as $session) {
			if ($count == 5) {
				break;
			}

			try {
				$loader = $ELLUMINATE_CONTAINER['sessionLoader'];
				$pageSession = $loader->getSessionById($session->id);
				if ($pageSession->getSessionType() == Elluminate_Group_Session::GROUP_CHILD_SESSION_TYPE){
					$cm = get_coursemodule_from_instance('elluminate', $pageSession->groupparentid,$pageSession->course);
				} else {
					$cm = get_coursemodule_from_instance('elluminate', $pageSession->id,$pageSession->course);
				}
				$context = context_module::instance($cm->id);
				$url = new moodle_url(new moodle_url(Elluminate_HTML_Session_View::getPageUrl($cm->id)));
				$permissions = $ELLUMINATE_CONTAINER['sessionPermissions'];
				$permissions->setContext($context);
				$permissions->courseModule = $cm;
				$permissions->userid = $USER->id;
				$permissions->pageSession = $pageSession;
			} catch (Elluminate_Exception $e) {
				return $this->display_block_error_msg("Session Load Error: " . get_string($e->getUserMessage(), 'elluminate'));
			} catch (Exception $e) {
				$logger->error("Session Load Error: ".$e->getMessage()."pageSession->id=".$pageSession->id.", pageSession->course".$pageSession->course.", pageSession->groupparentid=".$pageSession->groupparentid);
				return $this->display_block_error_msg("Session Load Error: " . get_string('user_error_processing', 'elluminate'));
			}
			# If this is a group session we need to add the groupid
			if ($pageSession->isGroupSession()) {
				$url->params(array('group' => $session->groupid));
			}
            if (!$permissions->doesUserHaveViewPermissionsForSession()) {
				$logger->debug("Failed doesUserHaveViewPermissionsForSession().  Not displaying SessionName: ".$session->sessionname.' CMID='.$cm->id);
	           	continue;
            }
            $context = context_course::instance($session->course);
            if (!is_enrolled($context)) {
            	$logger->debug("User not enrolled in course.  Not displaying SessionName: ".$session->sessionname.' CMID='.$cm->id);
            	continue;
            }
            
            $count++;
			$url->params(array('id' => $cm->id));
			$this->content->icons[] = '<img src="'.$OUTPUT->pix_url('icon', 'elluminate') . '" class="icon" alt="" />&nbsp;';
			$this->content->items[]= ' <a href="'.$url->out().'">'.$session->sessionname.'</a>';
		}

		return $this->content;
	}
}

?>
