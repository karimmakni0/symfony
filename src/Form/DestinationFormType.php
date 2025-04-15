<?php

namespace App\Form;

use App\Entity\Destinations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DestinationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Destination Name',
                'attr' => [
                    'class' => 'border-light',
                    'placeholder' => 'Enter destination name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a destination name',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Destination name must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => [
                    'class' => 'border-light',
                    'placeholder' => 'Enter location'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a location',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Location must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                    'rows' => '5',
                    'placeholder' => 'Enter description'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Description must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
        ;
        
        // Create constraints array for the image file
        $imageConstraints = [];
        
        // Only add NotBlank constraint if image is required
        if ($options['image_required']) {
            $imageConstraints[] = new NotBlank([
                'message' => 'Please upload an image',
            ]);
        }
        
        // Always add File constraint for type validation
        $imageConstraints[] = new File([
            'maxSize' => '2M',
            'mimeTypes' => [
                'image/jpeg',
                'image/png',
                'image/gif',
            ],
            'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG or GIF)',
        ]);
        
        // Add the image file field with appropriate constraints
        $builder->add('imageFile', FileType::class, [
            'label' => 'Destination Image',
            'mapped' => false,
            'required' => $options['image_required'],
            'constraints' => $imageConstraints,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Destinations::class,
            'image_required' => true, // By default, image is required for new destinations
        ]);
    }
}
