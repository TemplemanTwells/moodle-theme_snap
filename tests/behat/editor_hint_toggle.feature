@theme @theme_snap
Feature: the editor hint toggle should be ignored by Snap in Joule 2.9
  In order for users that have previously used this toggle to be put back to core experience
  As a teacher
  I need to see the editor hint messages in empty course sections

  Background:
    Given the following config values are set as admin:
      | theme_snap_disableeditorhints | true |
      | theme                         | snap |
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Create a URL resource in Snap theme
    Given I log in with snap as "teacher1"
    And I am on site homepage
    And I follow "Menu"
    And I follow "Course 1"
    Then I should see "Welcome to your new course Teacher 1."
    Then I should see "Start by describing what your course is about using text, images, audio & video."
    And I follow "Topic 1"
    Then I should see "Use this area to describe what this topic is about - with text, images, audio & video."
