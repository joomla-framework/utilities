<?php
/**
 * Part of the Joomla Framework Crypt Package
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Crypt\Cipher;

use Joomla\Crypt\CipherInterface;
use Joomla\Crypt\Exception\DecryptionException;
use Joomla\Crypt\Exception\EncryptionException;
use Joomla\Crypt\Exception\InvalidKeyTypeException;
use Joomla\Crypt\Key;
use ParagonIE\Sodium\Compat;

/**
 * Cipher for sodium algorithm encryption, decryption and key generation.
 *
 * @since  1.4.0
 */
class Sodium implements CipherInterface
{
	/**
	 * The message nonce to be used with encryption/decryption
	 *
	 * @var    string
	 * @since  1.4.0
	 */
	private $nonce;

	/**
	 * Method to decrypt a data string.
	 *
	 * @param   string  $data  The encrypted string to decrypt.
	 * @param   Key     $key   The key object to use for decryption.
	 *
	 * @return  string  The decrypted data string.
	 *
	 * @since   1.4.0
	 * @throws  DecryptionException if the data cannot be decrypted
	 * @throws  InvalidKeyTypeException if the key is not valid for the cipher
	 */
	public function decrypt($data, Key $key)
	{
		// Validate key.
		if ($key->getType() !== 'sodium')
		{
			throw new InvalidKeyTypeException('sodium', $key->getType());
		}

		if (!$this->nonce)
		{
			throw new DecryptionException('Missing nonce to decrypt data');
		}

		$decrypted = Compat::crypto_box_open(
			$data,
			$this->nonce,
			Compat::crypto_box_keypair_from_secretkey_and_publickey($key->getPrivate(), $key->getPublic())
		);

		if ($decrypted === false)
		{
			throw new DecryptionException('Malformed message or invalid MAC');
		}

		return $decrypted;
	}

	/**
	 * Method to encrypt a data string.
	 *
	 * @param   string  $data  The data string to encrypt.
	 * @param   Key     $key   The key object to use for encryption.
	 *
	 * @return  string  The encrypted data string.
	 *
	 * @since   1.4.0
	 * @throws  EncryptionException if the data cannot be encrypted
	 * @throws  InvalidKeyTypeException if the key is not valid for the cipher
	 */
	public function encrypt($data, Key $key)
	{
		// Validate key.
		if ($key->getType() !== 'sodium')
		{
			throw new InvalidKeyTypeException('sodium', $key->getType());
		}

		if (!$this->nonce)
		{
			throw new EncryptionException('Missing nonce to decrypt data');
		}

		return Compat::crypto_box(
			$data,
			$this->nonce,
			Compat::crypto_box_keypair_from_secretkey_and_publickey($key->getPrivate(), $key->getPublic())
		);
	}

	/**
	 * Method to generate a new encryption key object.
	 *
	 * @param   array  $options  Key generation options.
	 *
	 * @return  Key
	 *
	 * @since   1.4.0
	 * @throws  \RuntimeException
	 */
	public function generateKey(array $options = [])
	{
		// Generate the encryption key.
		$pair = Compat::crypto_box_keypair();

		return new Key('sodium', Compat::crypto_box_secretkey($pair), Compat::crypto_box_publickey($pair));
	}

	/**
	 * Check if the cipher is supported in this environment.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isSupported(): bool
	{
		return class_exists(Compat::class);
	}

	/**
	 * Set the nonce to use for encrypting/decrypting messages
	 *
	 * @param   string  $nonce  The message nonce
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	public function setNonce($nonce)
	{
		$this->nonce = $nonce;
	}
}
