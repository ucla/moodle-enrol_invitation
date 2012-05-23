<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

    <xsl:output method="html" indent="yes" version="4.0"/>
    <xsl:param name="pathPictures"/>
    <xsl:template match="*|text()|@*"> </xsl:template>

    <xsl:template match="/">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="root">

        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="windows">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="root/windows/windowsElement[type/text()='popupDial']">
        <div id="hiddenDivDial" class="opac"><xsl:text>&#160;</xsl:text></div>
        <div class="wimba_box general_font" id="popupDial"
            style="z-index:150;position:absolute;left:25%;top: 0px">
            <div class="wimba_boxTop">
                <div class="wimbaBoxTopDiv">
                    <span class="wimba_boxTopTitle phone">
                        <xsl:value-of select="popupDial/popupTitle"/>
                    </span>
                    <span title="close" class="wimba_close_box"
                        onclick="javascript:$('hiddenDivDial').remove();$('popupDial').remove()"
                        >Close</span>

                    <xsl:if test="popupDial/phones/phone!=''">
                        <span class="popupDialContent" style="margin-top:15px;">
                            <h1 style="padding-left:5px;padding-bottom:5px">
                                <xsl:value-of select="popupDial/phones/phoneLabel"/>
                            </h1>
                            <xsl:for-each select="popupDial/phones/phone">
                                <p>
                                    <span style="padding-left:30px"><xsl:value-of select="phoneDesc"/> 
                                        : <xsl:value-of select="number"/>
                                    </span>
                                </p>
                            </xsl:for-each>
                        </span>
                    </xsl:if>

                    <span class="popupDialContent" style="margin-top:15px;">
                        <h1 style="padding-left:5px;padding-bottom:5px">
                            <xsl:value-of select="popupDial/pin/pinLabel"/>
                        </h1>

                        <xsl:if test="popupDial/pin/instructor!=''">
                            <p>
                                <span style="padding-left:30px"> 
                                    Instructor : <xsl:value-of select="popupDial/pin/instructor"/>
                                </span>
                            </p>
                        </xsl:if>
                        <p>
                            <span style="padding-left:30px"> 
                                Student : <xsl:value-of select="popupDial/pin/student"/>
                            </span>
                        </p>
                    </span>
                    <p style="height:20px;padding-top:10px;padding-left:70px">
                        <a class="regular_btn" href="#"
                            onclick="javascript:$('hiddenDivDial').remove();$('popupDial').remove();">
                            <span style="width : 110px;">Ok</span>
                        </a>
                    </p>
                </div>

            </div>
            <div class="wimba_boxBottom">
                <div><xsl:text>&#160;</xsl:text></div>
            </div>
        </div>

    </xsl:template>



    <xsl:template match="windowsElement[type/text()='headerBar']">
        <div class="{type}">
            <div class="headerBarLeft">
                <span>Blackboard Collaborate</span>
            </div>
            <div class="headerBarRight">
                <xsl:if test="headerBarInformations/isInstructor='true'">
                    <select id="view" onchange="switchView()">
                        <xsl:if test="headerBarInformations/disabled='true'">
                            <xsl:attribute name="disabled">disabled</xsl:attribute>
                        </xsl:if>
                        <option value="normal">
                            <xsl:value-of select="headerBarInformations/instructorView"/>
                        </option>
                        <option value="student">
                            <xsl:value-of select="headerBarInformations/studentView"/>
                        </option>
                    </select>
                </xsl:if>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='contextBar']">
        <div class="{type}">
            <div class="contextBarLeft">
                <label class="nameElement">
                    <xsl:value-of select="contextBarInformations/name"/>
                </label>
                <label>
                    <xsl:value-of select="contextBarInformations/context"/>
                </label>
            </div>
        </div>
    </xsl:template>



    <xsl:template match="windowsElement[type/text()='toolBar']">
        <div class="toolbar">
            <ul class="toolbar_list" id="toolBar">
                <xsl:apply-templates/>
            </ul>
            <div style="padding-top: 22px;float:right;">
                <div id="searchBox" class="searchBox">
                    <input type="search" name="searchField" id="searchField"/>
                    <a id="searchFieldResetBtn"><xsl:text>&#160;</xsl:text></a>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='advancedPopup']">
        <div id="hiddenDivAdvanced" class="opac" style="display:none"><xsl:text>&#160;</xsl:text></div>
        <div class="wimba_box" id="advancedPopup"
            style="width:350px;z-index:150;display:none;position:absolute;left: 25%;top: 20px;">
            <div class="wimba_boxTop">
                <div class="wimbaBoxTopDiv">
                    <span class="wimba_boxTopTitle" style="width:300px;">
                        <xsl:value-of select="advancedPopup/popupTitle"/>
                    </span>
                    <span title="close" class="wimba_close_box"
                        onclick="javascript:cancelAdvanced()">Close</span>

                    <p class="wimba_boxText" style="padding:20px">
                        <xsl:value-of select="advancedPopup/popupSentence"/>
                    </p>
                    <p style="height:20px;padding-top:10px;padding-left:20px">
                        <a class="regular_btn" href="#" onclick="javascript:cancelAdvanced()">
                            <span style="width:110px">Cancel</span>
                        </a>

                        <input class="regular_btn-submit" style="margin-left:5px;" type="button" id="advancedOk" Value="Ok"/>
                    </p>
                </div>
            </div>
            <div class="wimba_boxBottom">
                <div><xsl:text>&#160;</xsl:text></div>
            </div>
        </div>

    </xsl:template>

    <xsl:template match="windowsElement[type/text()='filterBar']">
        <div id="{type}">
            <xsl:for-each select="filters/filter">
                <a href="#" class="filter{availibility}" id="filter_{name}" onclick="{action}"
                    onmouseover="javascript:onFilter('filter_{name}')" onmouseout="javascript:outFilter('filter_{name}')">
                    <span>
                        <xsl:value-of select="value"/>
                    </span>
                </a>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='list']">
        <div id="{type}">
            <xsl:for-each select="products/product">
                <div id="div_{type}" class="{productName} product" style="overflow:hidden;width:700px">
                    <table id="table_list"  cellspacing="0" cellpadding="1" border="0">
                        <tr style="background-color:#d3d5db;color:#5a6471;font-weight:bold" class="titlebar">
                           <xsl:for-each select="titles/title">
                            <td align="center"> <xsl:value-of select="value"/></td>
                            </xsl:for-each>
	                       
                        </tr>
                        <xsl:for-each select="listElements/Element">
                            <xsl:sort select="type" order="ascending"> </xsl:sort>
                            <xsl:sort select="nameDisplay" order="ascending"> </xsl:sort>
                            <tr class="element:available {type} preview:{preview} grade:{grade}" id="{id}" name="{nameDisplay}">
                                <xsl:choose>
                                    <xsl:when test="type='liveclassroom'">
                                        <td class="element_expand" width="20px">
                                            <xsl:choose>
                                                <xsl:when test="archives!=''">
                                                  <span class="roomWithArchives" onclick="javascript : hideArchive('{id}')" id="span{id}">
                                                        Display the archives
                                                    </span>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                  <span class="roomWithoutArchives" id="span{id}"
                                                  ondblclick="javascript:LaunchElement('{url}')"
                                                  onclick="javascript:clickElement('{id}','{product}','{type}')">
                                                    No Archives
                                                  </span>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </td>
                                    </xsl:when>
                                    <xsl:when test="type='orphanedarchivestudent'">
                                        <td class="element_expand"  width="20px">
                                            <span class="roomWithoutArchives" id="span{id}"
                                                ondblclick="javascript:LaunchElement('{url}')"
                                                onclick="javascript:clickElement('{id}','{product}','{type}')">
                                                    No Archives
                                            </span>
                                        </td>
                                    </xsl:when>
                                    <xsl:when test="type='orphanedarchive'">
                                        <td class="element_expand"  width="20px">
                                            <span class="roomWithoutArchives" id="span{id}"
                                                ondblclick="javascript:LaunchElement('{url}')"
                                                onclick="javascript:clickElement('{id}','{product}','{type}')">
                                                    No Archives
                                            </span>
                                        </td>
                                    </xsl:when>
                                </xsl:choose>
                                <td class="element_name" width="280px"  ondblclick="javascript:LaunchElement('{url}')"
                                        onclick="javascript:clickElement('{id}','{product}','{type}','{grade}')">
                                    <span class="element" alt="{nameDisplay}" title="{nameDisplay}">
                                        <xsl:value-of select="nameDisplay"/>
                                    </span>
                                </td>
                              
                                <xsl:choose>

                                    <xsl:when test="type='orphanedarchivestudent'">
                                        <td class="element_{preview}_ie"  width="75px" align="center"  ondblclick="javascript:LaunchElement('{url}')"
                                                onclick="javascript:clickElement('{id}','{product}','{type}')">
                                            <span alt="{tooltipAvailability}" title="{tooltipAvailability}">
                                                    This element is <xsl:value-of select="preview"/>
                                            </span>
                                        </td>
                                        <td  width="100px" style="padding-top:2px">
	                                      <xsl:choose>
			                                <xsl:when test="canDownloadMp3='1'">
									          <span alt="Download MP3" title="Download MP3" onclick="javascript:downloadAudioFile('manageAction.php','getMp3Status','{id}')" class="element_download_mp3">
	                                            Download MP3
	                                          </span>
			                                </xsl:when>
			                                <xsl:otherwise>
			                                   <span  alt="The MP3 is not available" title="The MP3 is not available"  style="display: block; cursor: default; float: left; width: 50px; text-align: right;margin-right:10px">-</span>
			                                </xsl:otherwise>
			                             </xsl:choose>
			                            
			                             <xsl:choose>
			                                <xsl:when test="canDownloadMp4='1'">
			                                  <span alt="Download MP4" title="Download MP4" onclick="javascript:downloadAudioFile('manageAction.php','getMp4Status','{id}')" class="element_download_mp4">
	                                            Download MP4
	                                          </span>
			                                </xsl:when>
			                                <xsl:otherwise>
			                                   <span alt="The MP4 is not available" title="The MP4 is not available" syle="cursor:default;float:right">-</span>
			                                </xsl:otherwise>
			                            </xsl:choose>
	                                    </td>

                                    </xsl:when>
                                    <xsl:when test="type='orphanedarchive'">
                                        <td class="element_{preview}_ie"  width="75px" align="center"  ondblclick="javascript:LaunchElement('{url}')"
                                                onclick="javascript:clickElement('{id}','{product}','{type}')">
                                            <span alt="{tooltipAvailability}" title="{tooltipAvailability}">
                                                    This element is <xsl:value-of select="preview"/>
                                            </span>
                                        </td>
										<td  width="100px" style="padding-top:2px">
	                                      <xsl:choose>
			                                <xsl:when test="canDownloadMp3='1'">
									          <span alt="Download MP3" title="Download MP3" onclick="javascript:downloadAudioFile('manageAction.php','getMp3Status','{id}')" class="element_download_mp3">
	                                            Download MP3
	                                          </span>
			                                </xsl:when>
			                                <xsl:otherwise>
			                                   <span  alt="The MP3 is not available" title="The MP3 is not available"  style="display: block; cursor: default; float: left; width: 50px; text-align: right;margin-right:10px">-</span>
			                                </xsl:otherwise>
			                             </xsl:choose>
			                            
			                             <xsl:choose>
			                                <xsl:when test="canDownloadMp4='1'">
			                                  <span alt="Download MP4" title="Download MP4" onclick="javascript:downloadAudioFile('manageAction.php','getMp4Status','{id}')" class="element_download_mp4">
	                                            Download MP4
	                                          </span>
			                                </xsl:when>
			                                <xsl:otherwise>
			                                   <span alt="The MP4 is not available" title="The MP4 is not available" syle="cursor:default;float:right">-</span>
			                                </xsl:otherwise>
			                            </xsl:choose>
	                                    </td>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <td class="element_{preview}_ie"  width="75px" align="center" ondblclick="javascript:LaunchElement('{url}')"
                                                onclick="javascript:clickElement('{id}','{product}','{type}')">
                                            <span alt="{tooltipAvailability}" title="{tooltipAvailability}">
                                                    This element is <xsl:value-of select="preview"/>
                                            </span>
                                        </td>
                                    </xsl:otherwise>
                                </xsl:choose>
                                <td  width="100px">
                                	<span><xsl:text>&#160;</xsl:text></span>
                                </td>
                                <xsl:choose>
                                    <xsl:when test="type='liveclassroom'">
                                        <td class="element_information"  width="100px" align="center">
                                            <span  alt="{tooltipDial}" title="{tooltipDial}" onclick="showInformation('{url}','{id}','{type}')">
                                                Get the Dial-In Information
                                            </span>
                                        </td>
                                    </xsl:when>
                                </xsl:choose>
                            </tr>

                            <xsl:for-each select="archives/archive">
                                <tr
                                    class="archive element:available parent:{parent} hideElement preview:{preview}"
                                    id="{id}" name="{nameDisplay}"
                                    >
                                    <td class="element_expand"  width="20px" onclick="javascript:clickElement('{id}','{product}','{type}')" ondblclick="javascript:LaunchElement('{url}')">
                                        <span class="subItem" id="{id}subItem"> </span>
                                    </td>
                                    <td class="list_name" width="280px" onclick="javascript:clickElement('{id}','{product}','{type}')" ondblclick="javascript:LaunchElement('{url}')">
                                        <span class="">
                                            <xsl:value-of select="nameDisplay"/>
                                        </span>
                                    </td>
                                    
                                    <td class="element_{preview}_ie" align="center"  id="{id}Availability"  width="75px" onclick="javascript:clickElement('{id}','{product}','{type}')" ondblclick="javascript:LaunchElement('{url}')">
                                        <span alt="{tooltipAvailability}" title="{tooltipAvailability}">
                                            This element is <xsl:value-of select="preview"/>
                                        </span>
                                    </td>
                                    <td  width="100px" style="padding-top:2px">
                                      <xsl:choose>
		                                <xsl:when test="canDownloadMp3='1'">
								          <span alt="Download MP3" title="Download MP3" onclick="javascript:downloadAudioFile('manageAction.php','getMp3Status','{id}')" class="element_download_mp3">
                                            Download MP3
                                          </span>
		                                </xsl:when>
		                                <xsl:otherwise>
		                                   <span  alt="The MP3 is not available" title="The MP3 is not available"  style="display: block; cursor: default; float: left; width: 50px; text-align: right;margin-right:10px">-</span>
		                                </xsl:otherwise>
		                             </xsl:choose>
		                            
		                             <xsl:choose>
		                                <xsl:when test="canDownloadMp4='1'">
		                                  <span alt="Download MP4" title="Download MP4" onclick="javascript:downloadAudioFile('manageAction.php','getMp4Status','{id}')" class="element_download_mp4">
                                            Download MP4
                                          </span>
		                                </xsl:when>
		                                <xsl:otherwise>
		                                   <span alt="The MP4 is not available" title="The MP4 is not available" syle="cursor:default;float:right">-</span>
		                                </xsl:otherwise>
		                            </xsl:choose>
                                    </td>
									<td  width="100px" onclick="javascript:clickElement('{id}','{product}','{type}')">
	                                	<span><xsl:text>&#160;</xsl:text></span>
	                                </td>
                                </tr>
                            </xsl:for-each>

                        </xsl:for-each>
                    </table>
                </div>
                <div class="hideElement" id="div_{type}_NoElement">
                    <span style="margin-left:25px">
                        <xsl:value-of select="NoElementSentence"/>
                    </span>
                </div>
                <div class="hideElement" id="div_{type}_More" type="productMore">
                    <span/>
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>



    <xsl:template match="windowsElement[type/text()='tabs']">
        <div id="tabs">
            <ul class="module_tab">
                <xsl:for-each select="tabsInformations/tabInformation">
                    <li class="{style}"  id="tab{id}">
                        <span id="tab{id}span"
                            onclick="javascript:onTab('{id}','{additionalFunction}')">
                            <xsl:choose>
                                <xsl:when test="style!='disabled'">
                                	<xsl:attribute name="title">active</xsl:attribute>
                                    <a href="#">
                                        <xsl:value-of select="name"/>
                                    </a>
                                </xsl:when>
                                <xsl:otherwise>
                                 	<xsl:attribute name="title">disabled</xsl:attribute>
                                    <xsl:value-of select="name"/>
                                </xsl:otherwise>
                            </xsl:choose>
                        </span>
                    </li>
                </xsl:for-each>
            </ul>     
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='tabsContent']">
        <form method="post" name="myform">
            <xsl:for-each select="tabsContent/tabContent">
                <div class="tabContent{style}" style="display:{display}" id="div{id}">
                    <fieldset>
                        <xsl:for-each select="panelLine">
                            <p id="{id}" class="{style} {context}">
                                <xsl:for-each select="lineElement">
                                    <xsl:if test="type = 'select'">
                                        <select id="{id}" name="{name}" class="{style}">
                                            <xsl:if test="disabled ='true'">
                                                <xsl:attribute name="disabled">
                                                    disabled
                                                 </xsl:attribute>
                                            </xsl:if>
                                            <xsl:for-each select="options/option">

                                                <option value="{value}">
                                                  <xsl:if test="selected ='true'">
                                                    <xsl:attribute name="selected">true</xsl:attribute>
                                                  </xsl:if>
                                                  <xsl:value-of select="display"/>
                                                </option>


                                            </xsl:for-each>
                                        </select>
                                    </xsl:if>

                                    <xsl:if test="type = 'label'">
                                        <label>
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <xsl:value-of select="display"/>
                                        </label>
                                    </xsl:if>
                                    <xsl:if test="type = 'link'">
                                        <a>
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <xsl:value-of select="display"/>
                                        </a>
                                    </xsl:if>
                                     <xsl:if test="type = 'img'">
                                        <img>
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <xsl:value-of select="display"/>
                                        </img>
                                    </xsl:if>
                                    <xsl:if test="type = 'custom'">
                                        <label id="label">
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <span class="{firstStyle}">
                                                <xsl:value-of select="firstPart"/>
                                            </span>
                                            <xsl:value-of select="secondPart"/>
                                        </label>
                                    </xsl:if>
                                    <xsl:if test="type = 'span'">
                                        <span id="span">
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <xsl:value-of select="display"/>
                                        </span>
                                    </xsl:if>
                                    <xsl:if test="type = 'textarea'">
                                        <textarea>
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:if test="name = 'desc'">
                                                  <xsl:value-of select="value"/>
                                                </xsl:if>
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                            <xsl:value-of select="display"/>
                                        </textarea>
                                    </xsl:if>

                                    <xsl:if test="type = 'input'">
                                        <input>
                                            <xsl:for-each select="parameters/parameter">
                                                <xsl:attribute name="{name}">
                                                  <xsl:value-of select="value"/>
                                                </xsl:attribute>
                                            </xsl:for-each>
                                        </input>
                                    </xsl:if>

                                    <xsl:if test="type = 'br'">
                                        <br> </br>
                                    </xsl:if>
                                    <xsl:if test="type = 'hr'">
                                        <xsl:attribute name="style"> border-top: solid 1px #F0F0F0;</xsl:attribute>
                                    </xsl:if>
                                </xsl:for-each>
                            </p>
                        </xsl:for-each>
                    </fieldset>
                </div>
            </xsl:for-each>
        </form>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='productChoice']">
        <div class="{type}" id="{type}">
            <table cellspacing="0" cellpadding="0" width="95%">
                <xsl:for-each select="products/product">
                    <tr onclick="{action}">
                        <td align="center" class="product_choice_left">
                            <img width="24px" heigth="24px" src="{pictureUrl}"/>
                            <br/>
                            <label>
                                <xsl:value-of select="value"/>
                            </label>
                        </td>
                        <td class="product_choice_right">
                            <xsl:value-of select="description"/>
                        </td>
                    </tr>
                </xsl:for-each>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='validationBar']">
        <div class="{type}" id="{type}">
            <ul class="regular_btn_list" style="float:right;padding-top:2px">

                <xsl:for-each select="validationElements/validationButton">
                    <li class="{style}">
                        <xsl:choose>
                            <xsl:when test="type = 'submit'">
                                <input type="submit" class="regular_btn-submit" onclick="{action}" value="{value}"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <a class="regular_btn" href="#" onclick="{action}">
                                    <span style="width : 110px;">
                                        <xsl:value-of select="value"/>
                                    </span>
                                </a>
                            </xsl:otherwise>
                        </xsl:choose>
                    </li>
                </xsl:for-each>
            </ul>
        </div>
    </xsl:template>


    <xsl:template match="menuElements">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="menuElement[type/text()='button']">
        <xsl:choose>
            <xsl:when test="availibility = 'disabled'">
                <li class="toolbar_btn-disabled" context="{typeOfUser}" state="{availibility}" id="button_{value}_li">
                    <span title="{value} disabled" alt="{value} disabled" class="{category}_btn" onclick="{action}" id="button_{value}">
                        <xsl:value-of select="value"/>
                    </span>
                </li>
            </xsl:when>
            <xsl:otherwise>
                <li class="toolbar_btn" context="{typeOfUser}" state="{availibility}">
                    <a title="{value}" alt="{value}" href="#" class="{category}_btn" onclick="{action}" id="button_{value}">
                        <xsl:value-of select="value"/>
                    </a>
                </li>
            </xsl:otherwise>
        </xsl:choose>

    </xsl:template>


    <xsl:template match="windowsElement[type/text()='message']">
        <div class="{type}" id="{type}">
            <span class="info">Info:</span>
            <span>
                <xsl:value-of select="message/value"/>
            </span>
            <span class="close" onclick="closeMessageBar()">Close</span>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='error']">
        <div class="headerBar">
            <div class="headerBarLeft">
                <span>Blackboard Collaborate</span>
            </div>
        </div>
        <div class="error_frame" style="background-color:#FFD0D0;height:217px;padding-top:150px;padding-left:40px">
            <div style="position:absolute;left:40px;">
                <span class="warning"/>
            </div>
            <span class="error_title">Error : <xsl:value-of select="message/value"/></span>
        </div>
    </xsl:template>

    <xsl:template match="windowsElement[type/text()='podcasterComponent']">
        <table id="MainTable" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border:solid 1px #808080">
            <tr>
                <td>
                    <div id="headerBar" class="headerBar">
                        <table cellpadding="0" cellspacing="0" style="padding-left:15px">
                            <tbody>
                                <tr>
                                    <td align="left" style="padding-left:10px">
                                        <img width="24px" heigth="24px" src="{$pathPictures}/items/headerbar-wimba_podcaster_icon.png"/>
                                    </td>
                                    <td>
                                        <img width="180px" heigth="32px" src="{$pathPictures}/items/headerbar-wimbapodcaster.png"/>
                                    </td>
                                    <td align="right" style="padding-right:10px">
                                        <select id="rids" onchange="getRss('{podcasterComponent/url}',this.value)">
                                            <xsl:for-each select="podcasterComponent/podcasters/podcaster">
                                                <option value="{id}">
                                                  <xsl:value-of select="name"/>
                                                </option>
                                            </xsl:for-each>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </td>
            </tr>
            <tr>
                <td height="100px" valign="top">
                    <div id="rss"> </div>
                </td>
            </tr>
            <tr>
                <td height="10px"> </td>
            </tr>
            <tr>
                <td>
                    <div id="validationBar" class="validationBar">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:solid 1px #808080;padding-left:15px">
                            <tr>
                                <td align="left" width="5%">
                                    <img width="16px" heigth="16px"
                                        src="{$pathPictures}/items/listitem-pcicon.png"/>
                                </td>
                                <td align="left" onclick="launchPodcast('{podcasterComponent/url}')" class="link">
                                 Click here to launch the podcast 
                                </td>
                            </tr>

                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </xsl:template>

</xsl:stylesheet>
