<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Trait - format section
 * Code that is shared between course_format_topic_renderer.php and course_format_weeks_renderer.php
 * Used for section outputs.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

include_once('general_section_trait.php');

trait format_section_trait {

    use general_section_trait;

    /**
     * Based on get_nav_links function in class format_section_renderer_base
     * This function has been modified to provide a link to section 0
     * Generate next/previous section links for naviation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        global $OUTPUT;
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back > -1 and empty($links['previous'])) {
            if ($canviewhidden
            || $sections[$back]->uservisible
            || $sections[$back]->availableinfo) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }

                $previouslink = html_writer::tag('span', $OUTPUT->larrow(), array('class' => 'larrow'));
                $previouslink .= get_section_name($course, $sections[$back]);
                if ($back > 0 ) {
                    $courseurl = course_get_url($course, $back);
                } else {
                    // We have to create the course section url manually if its 0.
                    $courseurl = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $back));
                }
                $links['previous'] = html_writer::link($courseurl, $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        while ($forward <= $course->numsections and empty($links['next'])) {
            if ($canviewhidden
            || $sections[$forward]->uservisible
            || $sections[$forward]->availableinfo) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', $OUTPUT->rarrow(), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
    }

    /**
     * Create target link content
     *
     * @param $name
     * @param $arrow
     * @param $string
     * @return string
     */
    private function target_link_content($name, $arrow, $string) {
        $html = html_writer::div($arrow, 'nav_icon');
        $html .= html_writer::start_span('text');
        $html .= html_writer::span($string, 'nav_guide');
        $html .= html_writer::empty_tag('br');
        $html .= $name;
        $html .= html_writer::end_tag('span');
        return $html;
    }


    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {

        if ($section->section === 0) {
            return [];
        }

        $coursecontext = context_course::instance($course->id);
        $isstealth = isset($course->numsections) && ($section->section > $course->numsections);

        if ($onsectionpage) {
            $baseurl = course_get_url($course, $section->section);
        } else {
            $baseurl = course_get_url($course);
        }
        $baseurl->param('sesskey', sesskey());

        $controls = array();

        if (!$isstealth && !$onsectionpage && has_capability('moodle/course:movesections', $coursecontext)) {
            $url = '#section-'.$section->section;
            $snap_move_section = "<img class='svg-icon' alt='' role='presentation' src='".$this->output->pix_url('move', 'theme')."'>";
            $movestring = get_string('move', 'theme_snap', format_string($section->name));
            $controls[] = html_writer::link($url, $snap_move_section ,
            array('title' => $movestring, 'alt' => $movestring, 'class' => 'snap-move', 'data-id' => $section->section));
        }

        $url = clone($baseurl);
        if (!$isstealth && has_capability('moodle/course:sectionvisibility', $coursecontext)) {
            if ($section->visible) { // Show the hide/show eye.
                $strhidefromothers = get_string('hidefromothers', 'format_'.$course->format);
                $url->param('hide', $section->section);
                $controls[] = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/hide'),
                    'class' => 'icon hide', 'alt' => $strhidefromothers)),
                    array('title' => $strhidefromothers, 'class' => 'editing_showhide'));
            } else {
                $strshowfromothers = get_string('showfromothers', 'format_'.$course->format);
                $url->param('show',  $section->section);
                $controls[] = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/show'),
                    'class' => 'icon hide', 'alt' => $strshowfromothers)),
                    array('title' => $strshowfromothers, 'class' => 'editing_showhide'));
            }
        }

        if (course_can_delete_section($course, $section)) {
            if (get_string_manager()->string_exists('deletesection', 'format_'.$course->format)) {
                $strdelete = get_string('deletesection', 'format_'.$course->format);
            } else {
                $strdelete = get_string('deletesection');
            }
            $url = new moodle_url('/course/editsection.php', array('id' => $section->id,
                'sr' => $onsectionpage ? $section->section : 0, 'delete' => 1));
            $controls[] = html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/delete'),
                    'class' => 'icon delete', 'alt' => $strdelete)),
                array('title' => $strdelete));
        }

        if ($course->format === 'topics') {
            if (!$isstealth && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
                $url = clone($baseurl);
                if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                    $url->param('marker', 0);
                    $controls[] = html_writer::link($url,
                        html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
                        'class' => 'icon ', 'alt' => get_string('markedthistopic'))),
                        array('title' => get_string('markedthistopic'), 'class' => 'editing_highlight'));
                } else {
                    $url->param('marker', $section->section);
                    $controls[] = html_writer::link($url,
                        html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
                        'class' => 'icon', 'alt' => get_string('markthistopic'))),
                        array('title' => get_string('markthistopic'), 'class' => 'editing_highlight'));
                }
            }
        }

        return $controls;
    }

    /**
     *
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE, $USER, $CFG;

        $o = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        if ($this->is_section_conditional($section)) {
            $canviewhiddensections = has_capability(
                'moodle/course:viewhiddensections',
                context_course::instance($course->id)
            );
            if (!$section->uservisible || $canviewhiddensections) {
                $sectionstyle .= ' conditional';
            }
        }

        // SHAME - the tabindex is intefering with moodle js.
        // SHAME - Remove tabindex when editing menu is shown.
        $sectionarrayvars = array('id' => 'section-'.$section->section,
        'class' => 'section main clearfix'.$sectionstyle,
        'role' => 'article',
        'aria-label' => get_section_name($course, $section));
        if (!$PAGE->user_is_editing()) {
            $sectionarrayvars['tabindex'] = '-1';
        }

        $o .= html_writer::start_tag('li', $sectionarrayvars);
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null.
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one.
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }

        $context = context_course::instance($course->id);

        $sectiontitle = get_section_name($course, $section);
        // Better first section title.
        if ($sectiontitle == get_string('general') && $section->section == 0) {
            $sectiontitle = get_string('introduction', 'theme_snap');
        }

        // Untitled topic title.
        $testemptytitle = get_string('topic').' '.$section->section;
        if ($sectiontitle == $testemptytitle && has_capability('moodle/course:update', $context)) {
          $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
          $o .= "<h2 class='sectionname'><a href='$url' title='".s(get_string('editcoursetopic', 'theme_snap'))."'>".get_string('defaulttopictitle', 'theme_snap')."</a></h2>";
        }
        else {
          $o .= $this->output->heading($sectiontitle, 2, 'sectionname' . $classes);
        }

        // Section drop zone.
        $caneditcourse = has_capability('moodle/course:update', $context);
        if ($caneditcourse && $section->section != 0) {
            $o .= "<a class='snap-drop section-drop' data-id='$section->section' data-title='".
                    s($sectiontitle)."' href='#'>_</a>";
        }

        // Section editing commands.
        $sectiontoolsarray = $this->section_edit_controls($course, $section, false);

        if (has_capability('moodle/course:update', $context)) {
            if (!empty($sectiontoolsarray)) {
              $sectiontools = implode(' ', $sectiontoolsarray);
              $o .= html_writer::tag('div', $sectiontools, array(
                  'class' => 'snap-section-editing actions',
                  'role' => 'region',
                  'aria-label' => get_string('topicactions', 'theme_snap')
              ));
            }
        }

        // Availabiliy message.
        $o .= "<div class='snap-restrictions-meta'>
        <div class='text text-danger'>".$this->section_availability_message($section,
            has_capability('moodle/course:viewhiddensections', $context))."</div>
        </div>";

        // Section summary/body text.
        $o .= "<div class='summary'>";
        $summarytext = $this->format_summary_text($section);

        $canupdatecourse = has_capability('moodle/course:update', $context);

        // Welcome message when no summary text.
        if (empty($summarytext) && $canupdatecourse) {
          $summarytext = "<p>".get_string('defaultsummary', 'theme_snap')."</p>";
          if ($section->section == 0) {
              $editorname = format_string(fullname($USER));
              $summarytext = "<p>".get_string('defaultintrosummary', 'theme_snap', $editorname)."</p>";
          }
        }

        $o .= $summarytext;
        if ($canupdatecourse) {
            $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
            $o .= "<a href='$url' class='edit-summary'>".get_string('editcoursetopic', 'theme_snap')."</a>";
        }
        $o .= "</div>";

        return $o;
    }

    /**
     * Next and previous links for Snap theme sections
     *
     * Mostly a spruced up version of the get_nav_links logic, since that
     * renderer mixes the logic of retrieving and building the link targets
     * based on availability with creating the HTML to display them niceley.
     * @return string
     */
    protected function next_previous($course, $sections, $sectionno) {
        $course = course_get_format($course)->get_course();

        $previousarrow = '<i class="icon-arrow-left"></i>';
        $nextarrow = '<i class="icon-arrow-right"></i>';

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $previous = '';
        $target = $sectionno - 1;
        while ($target >= 0 && empty($previous)) {
            if ($canviewhidden
                || $sections[$target]->uservisible
                || $sections[$target]->availableinfo) {
                $attributes = array('class' => 'previous_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] .= ' dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                // Better first section title.
                if ($sectionname == get_string('general')) {
                    $sectionname = get_string('introduction', 'theme_snap');
                }
                $previousstring = get_string('previoussection', 'theme_snap');
                $linkcontent = $this->target_link_content($sectionname, $previousarrow, $previousstring);
                $url = "#section-$target";
                $previous = html_writer::link($url, $linkcontent, $attributes);
            }
            $target--;
        }

        $next = '';
        $target = $sectionno + 1;
        while ($target <= $course->numsections && empty($next)) {
            if ($canviewhidden
                || $sections[$target]->uservisible
                || $sections[$target]->availableinfo) {
                $attributes = array('class' => 'next_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] .= ' dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                // Better first section title.
                if ($sectionname == get_string('general')) {
                    $sectionname = get_string('introduction', 'theme_snap');
                }
                $nextstring = get_string('nextsection', 'theme_snap');
                $linkcontent = $this->target_link_content($sectionname, $nextarrow, $nextstring);
                $url = "#section-$target";
                $next = html_writer::link($url, $linkcontent, $attributes);
            }
            $target++;
        }
        return "<nav class='section_footer'>".$previous.$next."</nav>";
    }


    // Basically unchanged from the core version  but inserts calls to
    // theme_snap_next_previous to add some navigation .
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            if ($section > $course->numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }

            $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id));

            // Student check.
            if (!$canviewhidden) {
                $conditional = $this->is_section_conditional($thissection);
                // HIDDEN SECTION - If nothing in show hidden sections, and course section is not visible - don't print.
                if (!$conditional && $course->hiddensections && !$thissection->visible) {
                    continue;
                }
                // CONDITIONAL SECTIONS - If its not visible to the user and we have no info why - don't print.
                if ($conditional && !$thissection->uservisible && !$thissection->availableinfo) {
                    continue;
                }
                // If hidden sections are collapsed - print a fake li.
                if (!$conditional && !$course->hiddensections && !$thissection->visible) {
                    echo $this->section_hidden($section);
                    continue;
                }
            }

            echo $this->section_header($thissection, $course, false, 0);

            // GThomas 21st Dec 2015 - Only output assets inside section if the section is user visible.
            // Otherwise you can see them, click on them and it takes you to an error page complaining that they
            // are restricted!
            if ($thissection->uservisible) {
                 echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                 // SLamour Aug 2015 - make add asset visible without turning editing on
                 // N.B. this function handles the can edit permissions.
                 echo $this->course_section_add_cm_control($course, $section, 0);

                if (!$PAGE->user_is_editing()) {
                    echo $this->next_previous($course, $modinfo->get_section_info_all(), $section);
                }
            }
            echo $this->section_footer();
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                // Don't print add resources/activities of 'stealth' sections.
                // echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }
        }
        echo $this->end_section_list();
    }

    protected function end_section_list() {
        global $COURSE;

        $output = html_writer::end_tag('ul');
        $output .= $this->change_num_sections($COURSE);
        $output .= "<section id='coursetools' class='clearfix'>";
        $output .= snap_shared::coursetools_svg_icons();
        $output .= snap_shared::appendices();
        $output .= "</section>";
        return $output;
    }


    /**
     * Render a form to create a new course section, prompting for basic info.
     *
     * @return string
     */
    private function change_num_sections($course) {
        global $PAGE;

        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        if (!has_capability('moodle/course:update', $context)) {
            return '';
        }

        $url = new moodle_url('/theme/snap/index.php', array(
            'sesskey'  => sesskey(),
            'action' => 'addsection',
            'contextid' => $context->id,
        ));

        $required = '';
        $defaulttitle = get_string('title', 'theme_snap');
        $sectionnum = $course->numsections;
        if ($course->format === 'topics') {
            $required = 'required';
        } else {
            // Take this part of code from /course/format/weeks/lib.php on functions
            // get_section_name($section) and get_section_dates($section).
            $oneweekseconds = 60*60*24*7;
            // Hack alert. We add 2 hours to avoid possible DST problems. (e.g. we go into daylight
            // savings and the date changes.
            $startdate = $course->startdate + (60 * 60 *2);
            $dates = new stdClass();
            $dates->start = $startdate + ($oneweekseconds * $sectionnum);
            $dates->end = $dates->start + $oneweekseconds;
            // We subtract 24 hours for display purposes.
            $dates->end = ($dates->end - (60 * 60 * 24));
            $dateformat = get_string('strftimedateshort');
            $weekday = userdate($dates->start, $dateformat);
            $endweekday = userdate($dates->end, $dateformat);
            $datesection = $weekday.' - '.$endweekday;
        }
        $heading = get_string('addanewsection', 'theme_snap');
        $output = "<section id='snap-add-new-section' class='clearfix' tabindex='-1'>
        <h3>$heading</h3>";
        $output .= html_writer::start_tag('form', array(
            'method' => 'post',
            'action' => $url->out_omit_querystring()
        ));
        $output .= html_writer::input_hidden_params($url);
        $output .= '<div class="form-group">';
        $output .= "<label for='newsection' class='sr-only'>".get_string('title', 'theme_snap')."</label>";
        if ($course->format === 'topics') {
            $output .= "<input class='h3' id='newsection' type='text' maxlength='250' name='newsection' $required placeholder='".get_string('title', 'theme_snap')."'>";
        } else {
            $output .= "<h3>".$defaulttitle.': '.$datesection."</h3>";
        }
        $output .= '</div>';
        $output .= '<div class="form-group">';
        $output .= "<label for='summary'>".get_string('contents', 'theme_snap')."</label>";
        $output .= print_textarea(true, 10, 150, "100%",
            "auto", "summary", '', $course->id, true);
        $output .= '</div>';
        $output .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'addtopic',
            'value' => get_string('createsection', 'theme_snap'),
        ));
        $output .= html_writer::end_tag('form');
        $output .= '</section>';
        return $output;
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * Note, if theme overwrites this function and it does not use modchooser,
     * see also {@link core_course_renderer::add_modchoosertoggle()}
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        // check to see if user can add menus and there are modules to add
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
                || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }
        // Retrieve all modules with associated metadata
        $modules = get_module_metadata($course, $modnames, $sectionreturn);
        $urlparams = array('section' => $section);
            // S Lamour Aug 2015 - show activity picker
            // moodle is adding a link around the span in a span with js - yay!! go moodle...
            $modchooser = "<div class='snap-modchooser btn section_add_menus'>
              <span class='section-modchooser-link'><span>".get_string('addresourceoractivity', 'theme_snap')."</span></span>
            </div>";
           $output = $this->courserenderer->course_modchooser($modules, $course) . $modchooser;

           // Add zone for quick uploading of files.
           $upload = '<form class="snap-dropzone">
              <label for="snap-drop-file-'.$section.'" class="snap-dropzone-label h6">'.get_string('dropzonelabel', 'theme_snap').'</label>
              <input type="file" multiple name="snap-drop-file-'.$section.'" id="snap-drop-file-'.$section.'" class="js-snap-drop-file sr-only"/>
              </form>';
           return $output.$upload;
    }

    /**
     * Always output the html for multiple sections, single section mode is not supported in Snap.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        return $this->print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
    }
}
