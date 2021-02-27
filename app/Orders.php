<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'orders';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['user_id','order_placed','config_id'];
	
	//
	protected $casts = [
			'order_placed' => 'array'
	];

	public function user()
	{
		return $this->belongsTo('App\User');
	}
}
