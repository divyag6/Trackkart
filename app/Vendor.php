<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'vendors';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name'];
	
	public function user()
	{
		return $this->hasMany('App\User');
	}
	public function vendorConfig()
	{
		return $this->hasMany('App\VendorConfig');
	}
	
	
	
}
