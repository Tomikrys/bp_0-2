<?php
namespace App\Service;

use App\Repository\UserRepository;

class GetAllUsersForSwitching
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Finds all users
     */
    public function findAll() {
        //$this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->userRepository->findAll();
    }
}