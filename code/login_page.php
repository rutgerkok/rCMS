<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<link href="<?php echo $this->get_url_themes().$this->get_sitevar("theme")."/main.css" ?>" rel="stylesheet" type="text/css" />
	</head>
	<body style="background-image:none;text-align:center">
		<div id="login">
			<h1><?php echo $this->get_sitevar('hometitle') ?></h1>
			<form action="" method="post">
				<p>
					<?php  echo $this->translations['55'] ?>
				</p>
				<p>
					<input type="password" name="key" id="key" style="width:10em;" />
					<script type="text/javascript">
						document.getElementById("key").focus();
					</script>
				</p>
				<p>
					<input type="submit" class="button" style="width:11em;" value="<?php  echo $this->translations['4'] ?>" />
				</p>
			</form>
		</div>
	</body>
</html>