<?php

$elluminateRoot = $CFG->dirroot . '/mod/elluminate/';


require_once($elluminateRoot . "include/Pimple.php");

//Audit Log
require_once($elluminateRoot . "elluminate/audit/constants.php");
require_once($elluminateRoot . "elluminate/audit/log.php");
require_once($elluminateRoot . "elluminate/audit/dao.php");
require_once($elluminateRoot . "elluminate/audit/report.php");

//Validation
require_once($elluminateRoot . "elluminate/validation/state.php");
require_once($elluminateRoot . "elluminate/validation/validator.php");

//Config
require_once($elluminateRoot . "elluminate/config/version.php");

//Licensing
require_once($elluminateRoot . "elluminate/license/dao.php");
require_once($elluminateRoot . "elluminate/license/manager.php");
require_once($elluminateRoot . "elluminate/license/entry.php");
require_once($elluminateRoot . "elluminate/license/constants.php");

//grading
require_once($elluminateRoot . "elluminate/grades/attendance.php");
require_once($elluminateRoot . "elluminate/grades/dao.php");
require_once($elluminateRoot . "elluminate/grades/gradebookentry.php");
require_once($elluminateRoot . "elluminate/grades/gradebook.php");
require_once($elluminateRoot . "elluminate/grades/factory.php");
require_once($elluminateRoot . "elluminate/grades/reports.php");

//Web
require_once($elluminateRoot . "elluminate/html/session/view.php");
require_once($elluminateRoot . "elluminate/html/usereditor.php");
require_once($elluminateRoot . "elluminate/html/recording/listview.php");
require_once($elluminateRoot . "elluminate/html/recording/detailview.php");
require_once($elluminateRoot . "elluminate/html/recording/playview.php");
require_once($elluminateRoot . "elluminate/html/recording/convertview.php");
require_once($elluminateRoot . "elluminate/html/recording/table.php");
require_once($elluminateRoot . "elluminate/html/recording/fileactions.php");
require_once($elluminateRoot . "elluminate/html/recording/actions.php");
require_once($elluminateRoot . "elluminate/html/preloadview.php");
require_once($elluminateRoot . "elluminate/html/logview.php");
require_once($elluminateRoot . "elluminate/html/licenseview.php");
require_once($elluminateRoot . "elluminate/html/attendview.php");
require_once($elluminateRoot . "elluminate/html/table.php");
require_once($elluminateRoot . "elluminate/html/output.php");
require_once($elluminateRoot . "elluminate/html/telephonysettings.php");

//Logger
require_once($elluminateRoot . "elluminate/logger/factory.php");
require_once($elluminateRoot . "elluminate/logger/logger.php");

//Session
require_once($elluminateRoot . "elluminate/session.php");
require_once($elluminateRoot . "elluminate/session/dao.php");
require_once($elluminateRoot . "elluminate/session/validator.php");
require_once($elluminateRoot . "elluminate/session/capabilities.php");
require_once($elluminateRoot . "elluminate/session/grading.php");
require_once($elluminateRoot . "elluminate/session/calendar.php");
require_once($elluminateRoot . "elluminate/session/users.php");
require_once($elluminateRoot . "elluminate/session/loader.php");
require_once($elluminateRoot . "elluminate/session/permissions.php");
require_once($elluminateRoot . "elluminate/session/factory.php");
require_once($elluminateRoot . "elluminate/session/configuration.php");

//Session Controllers
require_once($elluminateRoot . "elluminate/session/controller/add.php");
require_once($elluminateRoot . "elluminate/session/controller/update.php");

//Groups
require_once($elluminateRoot . "elluminate/group/access.php");
require_once($elluminateRoot . "elluminate/group/session.php");
require_once($elluminateRoot . "elluminate/group/dao.php");
require_once($elluminateRoot . "elluminate/group/childsession.php");
require_once($elluminateRoot . "elluminate/group/sessioninitializer.php");
require_once($elluminateRoot . "elluminate/group/switcher.php");
require_once($elluminateRoot . "elluminate/group/customnaming.php");

//Recordings
require_once($elluminateRoot . "elluminate/recording.php");
require_once($elluminateRoot . "elluminate/recordings/dao.php");
require_once($elluminateRoot . "elluminate/recordings/capabilities.php");
require_once($elluminateRoot . "elluminate/recordings/permissions.php");
require_once($elluminateRoot . "elluminate/recordings/loader.php");
require_once($elluminateRoot . "elluminate/recordings/utils.php");
require_once($elluminateRoot . "elluminate/recordings/file.php");
require_once($elluminateRoot . "elluminate/recordings/constants.php");
require_once($elluminateRoot . "elluminate/recordings/statusupdate.php");
require_once($elluminateRoot . "elluminate/recordings/factory.php");

//Preloads
require_once($elluminateRoot . "elluminate/preloads/validator.php");
require_once($elluminateRoot . "elluminate/preload.php");
require_once($elluminateRoot . "elluminate/preloads/factory.php");
require_once($elluminateRoot . "elluminate/preloads/dao.php");

//Telephony
require_once($elluminateRoot . "elluminate/telephony/manager.php");
require_once($elluminateRoot . "elluminate/telephony/instance.php");

//Moodle Helpers
require_once($elluminateRoot . "elluminate/moodle/dao.php");
require_once($elluminateRoot . "elluminate/moodle/calendar.php");
require_once($elluminateRoot . "elluminate/moodle/capabilities.php");
require_once($elluminateRoot . "elluminate/moodle/gradehelper.php");
require_once($elluminateRoot . "elluminate/moodle/groupaccess.php");
require_once($elluminateRoot . "elluminate/moodle/images.php");
require_once($elluminateRoot . "elluminate/moodle/table.php");
require_once($elluminateRoot . "elluminate/moodle/output.php");

//Settings Manager
require_once($elluminateRoot . "elluminate/config/settings.php");

//Web Services
require_once($elluminateRoot . "elluminate/ws/schedulingmanager.php");
require_once($elluminateRoot . "elluminate/ws/schedulingmanagerfactory.php");
require_once($elluminateRoot . "elluminate/ws/apiresponsehandler.php");

//SAS Response Handlers
require_once($elluminateRoot . "elluminate/ws/sas/response/recordingresponse.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/optionlicenseresponse.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/sessionid.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/sessionresponse.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/url.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/presentationresponse.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/recordingfileresponse.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/telephonytype.php");

//SAS Implementation
require_once($elluminateRoot . "elluminate/ws/sas/implementation.php");
require_once($elluminateRoot . "elluminate/ws/sas/response/factory.php");
require_once($elluminateRoot . "elluminate/ws/sas/sessionargs.php");

//ELM Implementation
require_once($elluminateRoot . "elluminate/ws/elm/implementation.php");
require_once($elluminateRoot . "elluminate/ws/elm/response/factory.php");
require_once($elluminateRoot . "elluminate/ws/elm/soappreloadclient.php");

//ELM Response Handlers
require_once($elluminateRoot . "elluminate/ws/elm/response/setsessionresponse.php");
require_once($elluminateRoot . "elluminate/ws/elm/response/url.php");
require_once($elluminateRoot . "elluminate/ws/elm/response/emailbody.php");
require_once($elluminateRoot . "elluminate/ws/elm/response/listrecordinglongresponse.php");
require_once($elluminateRoot . "elluminate/ws/elm/response/uploadrepositorypresentationresponse.php");

//SOAP
require_once($elluminateRoot . "elluminate/ws/soap/helper.php");
require_once($elluminateRoot . "elluminate/ws/soap/response.php");
require_once($elluminateRoot . "elluminate/ws/utils.php");

//Exception
require_once($elluminateRoot . "elluminate/exception.php");

//Caching
require_once($elluminateRoot . "elluminate/cache/manager.php");
require_once($elluminateRoot . "elluminate/cache/constants.php");
require_once($elluminateRoot . "elluminate/cache/dao.php");
require_once($elluminateRoot . "elluminate/cache/item.php");
require_once($elluminateRoot . "elluminate/cache/content.php");
require_once($elluminateRoot . "elluminate/cache/telephony/content.php");
require_once($elluminateRoot . "elluminate/cache/url/recordingcontent.php");
require_once($elluminateRoot . "elluminate/cache/url/guestlinkcontent.php");
