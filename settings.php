<style>
    .success {
		padding:       0.8em;
		margin-bottom: 1em;
		border:        2px solid #ddd;
		background:    #e6efc2;
		color:         #264409;
		border-color:  #c6d880;
	}

    .success a {
		color:         #264409;
	}

    .success p {
		margin:         0;
	}

    .wp-most-popular-light {
		color:          #777;
		font-size:      12px;
		margin-left:     1em;
	}
</style>
<div class="wrap">
    <?php
    if (!empty($errors)) {
        foreach($errors as $error) {
            $this->printErrorMessage($error);
        }
    }
    ?>
    <h2>Most Popular <span class="wp-most-popular-light">Version <?php echo MostPopular::$VERSION; ?></span></h2>
    <?php
    if ($isSaved) {
        $this->printSuccessMessage("Settings saved successfully.");
    }
    ?>
    <form name="most-popular" method="post" action="">
        <input type="hidden" name="isMostPopularSettings" value="Y" />
        <h3>Required Settings</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="api_source"><?php _e('Source'); ?></label></th>
                <td><?php $this->printSelectTag("api_source", array("none" => "None", "parsley" => "Parse.ly"), $options["api_source"]); ?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="api_key"><?php _e('API Key'); ?></label></th>
                <td><?php $this->printTextTag("api_key", $options["api_key"], array("size" => "20", "placeholder" => "newyorker.com")); ?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="api_secret"><?php _e('Secret'); ?></label></th>
                <td><?php $this->printTextTag("api_secret", $options["api_secret"], array("size" => "20", "placeholder" => $options["api_secret"])); ?></td>
			</tr>
            <tr valign="top">
                <th scope="row"><label for="refresh_rate"><?php _e('Refresh Rate'); ?> <span class="wp-most-popular-light">(minutes)</span></label></th>
                <td><?php $this->printSelectTag("refresh_rate", array("3" => "3", "5" => "5", "10" => "10", "20" => "20", "30" => "30", "60" => "60"), $options["refresh_rate"]); ?></td>
			</tr>
            <tr valign="top">
                <th scope="row"><label for="number_of_results"><?php _e('Number of results'); ?></label></th>
                <td><?php $this->printSelectTag("number_of_results", array("3" => "3", "5" => "5", "10" => "10"), $options["number_of_results"]); ?></td>
	</tr>
        </table>
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>
    </form>
</div>
