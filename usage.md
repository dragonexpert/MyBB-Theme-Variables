##Creating a New Variable:
1) Log into your Admin Control Panel.  
2) Click Templates & Style then Theme Variables  
3) Click on the Create Variable Button  
4) Fill out all fields and hit the button at the bottom.  
5) Once you have submitted the form you can see all variables for a theme by clicking on it.  
6) The usage column is what you will put in your template to call the variable.

##Using a Variable
1) Log into your Admin Control Panel.  
2) Click Templates & Style then the theme you want.
3) Insert the usage code that was generated from creating the variable into whichever template you wish.  
4) Save the template and it will be ready to go.

##Sample Variable
This will create a variable that shows content to only staff usergroups.  
1) Log into your Admin Control Panel.  
2) Click Templates & Style then Theme Variables  
3) Click The Create Variable Button  
4) For Theme choose 'All'.  
5) For usergroups choose whichever usergroups are considered staff.
6) For unique name choose 'staff_only'.
7) For Forums choose whichever you want.
8) For content, put what you want the person to see if they are staff. If you are using the premium version regex is supported so you can use $1 to represent
the content between the tags.  
9) If you are using the premium version you can set what you want the variable to display to those who aren't allowed to see it.  If you are using the normal version they will see nothing.  
10) Save the variable.
11) Go to templates and then whichever theme you want.
12) If you are using the premium version put [staff_only]Your content here[/staff_only] otherwise put @{staff_only} in whichever template you want the code to appear in.  
13) Save the template then browse the forum and it will be working.
