<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorConfig extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'vendor_config';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['email', 'password','vendor_id','user_id'];
	
	public function user()
	{
		return $this->belongsTo('App\User');
	}
	

}
