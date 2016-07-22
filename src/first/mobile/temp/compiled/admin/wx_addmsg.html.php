<?php echo $this->fetch('pageheader.htm'); ?>

<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,listtable.js')); ?>

<script type="text/javascript" src="../js/calendar.php"></script>

<link href="../js/calendar/calendar.css" rel="stylesheet" type="text/css" />

<div id="tabbody-div">

<form name="theForm" method="post" action="weixin.php?act=news&id=<?php echo $this->_var['corn']['id']; ?>">

  <table width="100%" cellpadding="3" cellspacing="1">

  <tbody>

  <tr><th colspan="2" align="center">给用户推送消息</th></tr>

 <tr>

    <td class="label">消息类型:</td>

    <td>

    <select name="msgtype">

		<option value="1" <?php if ($this->_var['msgtype'] == 'news'): ?>selected<?php endif; ?>>系统文章</option>

		<option value="2" <?php if ($this->_var['msgtype'] == 'text'): ?>selected<?php endif; ?>>自定义文本</option>

	</select>

    </td>

  </tr>

  <!--

  <tr>

    <td class="label">推送时间:</td>

    <td><input type="text" name="sendtime" size="25" id="sendtime" value="<?php echo $this->_var['sendymd']; ?>"><input name="selbtn1" type="button" id="selbtn1" onclick="return showCalendar('sendtime', '%Y-%m-%d %H:00:00', false, false, 'selbtn1');" value="选择" class="button"/></td>

  </tr>

  -->

  <tr>

    <td class="label">要推送的文章编号或者自定义内容:</td>

    <td><textarea name="artid" rows="5" cols="40"><?php echo $this->_var['artid']; ?></textarea><br />选择系统文章直接填写文章编号多个使用“,”分割。<br />选择自定义文本直接输入内容即可（支持超链接）。</td>

  </tr>

  <tr>

    <td colspan="2" align="center">

      <input type="submit" value="<?php echo $this->_var['lang']['button_submit']; ?>" class="button" />

    <input type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="button" />

    </td>

  </tr>

</tbody></table>

</form>

</div>



<?php echo $this->fetch('pagefooter.htm'); ?>